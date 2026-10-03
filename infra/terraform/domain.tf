data "aws_route53_zone" "application" {
  name         = var.domain_name
  private_zone = false
}

data "aws_cloudfront_cache_policy" "caching_disabled" {
  name = "Managed-CachingDisabled"
}

data "aws_cloudfront_origin_request_policy" "all_viewer_and_cloudfront_headers" {
  name = "Managed-AllViewerAndCloudFrontHeaders-2022-06"
}

locals {
  certificate_domains = toset([var.domain_name, "www.${var.domain_name}"])
}

resource "aws_acm_certificate" "application" {
  provider = aws.us_east_1

  domain_name               = var.domain_name
  subject_alternative_names = ["www.${var.domain_name}"]
  validation_method         = "DNS"

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_route53_record" "certificate_validation" {
  for_each = local.certificate_domains

  zone_id = data.aws_route53_zone.application.zone_id
  name = one([
    for option in aws_acm_certificate.application.domain_validation_options : option.resource_record_name
    if option.domain_name == each.value
  ])
  type = one([
    for option in aws_acm_certificate.application.domain_validation_options : option.resource_record_type
    if option.domain_name == each.value
  ])
  ttl = 300
  records = [one([
    for option in aws_acm_certificate.application.domain_validation_options : option.resource_record_value
    if option.domain_name == each.value
  ])]
}

resource "aws_acm_certificate_validation" "application" {
  provider = aws.us_east_1

  certificate_arn         = aws_acm_certificate.application.arn
  validation_record_fqdns = [for record in aws_route53_record.certificate_validation : record.fqdn]
}

resource "aws_route53_record" "origin" {
  zone_id = data.aws_route53_zone.application.zone_id
  name    = "origin.${var.domain_name}"
  type    = "A"
  ttl     = 60
  records = [module.compute.public_ip]
}

resource "aws_cloudfront_distribution" "application" {
  enabled         = true
  is_ipv6_enabled = true
  aliases         = [var.domain_name, "www.${var.domain_name}"]
  comment         = "Mothoq production application"
  http_version    = "http2and3"
  price_class     = "PriceClass_100"

  origin {
    domain_name = aws_route53_record.origin.fqdn
    origin_id   = "mothoq-ec2-origin"

    custom_origin_config {
      http_port              = 80
      https_port             = 443
      origin_protocol_policy = "https-only"
      origin_ssl_protocols   = ["TLSv1.2"]
    }
  }

  default_cache_behavior {
    target_origin_id         = "mothoq-ec2-origin"
    viewer_protocol_policy   = "redirect-to-https"
    allowed_methods          = ["DELETE", "GET", "HEAD", "OPTIONS", "PATCH", "POST", "PUT"]
    cached_methods           = ["GET", "HEAD", "OPTIONS"]
    compress                 = true
    cache_policy_id          = data.aws_cloudfront_cache_policy.caching_disabled.id
    origin_request_policy_id = data.aws_cloudfront_origin_request_policy.all_viewer_and_cloudfront_headers.id
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  viewer_certificate {
    acm_certificate_arn      = aws_acm_certificate_validation.application.certificate_arn
    minimum_protocol_version = "TLSv1.2_2021"
    ssl_support_method       = "sni-only"
  }
}

resource "aws_route53_record" "application_ipv4" {
  zone_id = data.aws_route53_zone.application.zone_id
  name    = var.domain_name
  type    = "A"

  alias {
    name                   = aws_cloudfront_distribution.application.domain_name
    zone_id                = aws_cloudfront_distribution.application.hosted_zone_id
    evaluate_target_health = false
  }
}

resource "aws_route53_record" "application_ipv6" {
  zone_id = data.aws_route53_zone.application.zone_id
  name    = var.domain_name
  type    = "AAAA"

  alias {
    name                   = aws_cloudfront_distribution.application.domain_name
    zone_id                = aws_cloudfront_distribution.application.hosted_zone_id
    evaluate_target_health = false
  }
}

resource "aws_route53_record" "www_ipv4" {
  zone_id = data.aws_route53_zone.application.zone_id
  name    = "www.${var.domain_name}"
  type    = "A"

  alias {
    name                   = aws_cloudfront_distribution.application.domain_name
    zone_id                = aws_cloudfront_distribution.application.hosted_zone_id
    evaluate_target_health = false
  }
}

resource "aws_route53_record" "www_ipv6" {
  zone_id = data.aws_route53_zone.application.zone_id
  name    = "www.${var.domain_name}"
  type    = "AAAA"

  alias {
    name                   = aws_cloudfront_distribution.application.domain_name
    zone_id                = aws_cloudfront_distribution.application.hosted_zone_id
    evaluate_target_health = false
  }
}

resource "aws_ses_domain_identity" "application" {
  domain = var.domain_name
}

resource "aws_sesv2_configuration_set" "application" {
  configuration_set_name = local.name

  reputation_options {
    reputation_metrics_enabled = true
  }
}

resource "aws_sns_topic" "ses_events" {
  name       = "${var.project_name}-ses-events"
  fifo_topic = false
}

resource "aws_sns_topic_policy" "ses_events" {
  arn = aws_sns_topic.ses_events.arn

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid       = "AllowSesConfigurationSet"
      Effect    = "Allow"
      Principal = { Service = "ses.amazonaws.com" }
      Action    = "sns:Publish"
      Resource  = aws_sns_topic.ses_events.arn
      Condition = {
        StringEquals = { "aws:SourceAccount" = data.aws_caller_identity.current.account_id }
        ArnEquals    = { "aws:SourceArn" = aws_sesv2_configuration_set.application.arn }
      }
    }]
  })
}

resource "aws_sqs_queue" "ses_events" {
  name                      = "${var.project_name}-ses-events-queue"
  fifo_queue                = false
  sqs_managed_sse_enabled   = true
  max_message_size          = 1048576
  message_retention_seconds = 345600
}

resource "aws_sqs_queue_policy" "ses_events" {
  queue_url = aws_sqs_queue.ses_events.url

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid       = "AllowSesEventsTopic"
        Effect    = "Allow"
        Principal = { Service = "sns.amazonaws.com" }
        Action    = "sqs:SendMessage"
        Resource  = aws_sqs_queue.ses_events.arn
        Condition = {
          ArnEquals = { "aws:SourceArn" = aws_sns_topic.ses_events.arn }
        }
      },
      {
        Sid       = "DenyOtherMessageSources"
        Effect    = "Deny"
        Principal = "*"
        Action    = "sqs:SendMessage"
        Resource  = aws_sqs_queue.ses_events.arn
        Condition = {
          ArnNotEquals = { "aws:SourceArn" = aws_sns_topic.ses_events.arn }
        }
      }
    ]
  })
}

resource "aws_sns_topic_subscription" "ses_events" {
  topic_arn            = aws_sns_topic.ses_events.arn
  protocol             = "sqs"
  endpoint             = aws_sqs_queue.ses_events.arn
  raw_message_delivery = true

  depends_on = [aws_sqs_queue_policy.ses_events]
}

resource "aws_sesv2_configuration_set_event_destination" "ses_events" {
  configuration_set_name = aws_sesv2_configuration_set.application.configuration_set_name
  event_destination_name = aws_sns_topic.ses_events.name

  event_destination {
    enabled              = true
    matching_event_types = ["DELIVERY", "BOUNCE", "COMPLAINT", "REJECT", "DELIVERY_DELAY"]

    sns_destination {
      topic_arn = aws_sns_topic.ses_events.arn
    }
  }

  depends_on = [aws_sns_topic_policy.ses_events, aws_sns_topic_subscription.ses_events]
}

resource "aws_route53_record" "ses_verification" {
  zone_id = data.aws_route53_zone.application.zone_id
  name    = "_amazonses.${var.domain_name}"
  type    = "TXT"
  ttl     = 300
  records = [aws_ses_domain_identity.application.verification_token]
}

resource "aws_ses_domain_identity_verification" "application" {
  domain     = aws_ses_domain_identity.application.id
  depends_on = [aws_route53_record.ses_verification]
}

resource "aws_ses_domain_dkim" "application" {
  domain = aws_ses_domain_identity.application.domain
}

resource "aws_route53_record" "ses_dkim" {
  count = 3

  zone_id = data.aws_route53_zone.application.zone_id
  name    = "${aws_ses_domain_dkim.application.dkim_tokens[count.index]}._domainkey.${var.domain_name}"
  type    = "CNAME"
  ttl     = 300
  records = ["${aws_ses_domain_dkim.application.dkim_tokens[count.index]}.dkim.amazonses.com"]
}

resource "aws_ses_domain_mail_from" "application" {
  domain                 = aws_ses_domain_identity.application.domain
  mail_from_domain       = "mail.${var.domain_name}"
  behavior_on_mx_failure = "UseDefaultValue"
}

resource "aws_route53_record" "ses_mail_from_mx" {
  zone_id = data.aws_route53_zone.application.zone_id
  name    = aws_ses_domain_mail_from.application.mail_from_domain
  type    = "MX"
  ttl     = 300
  records = ["10 feedback-smtp.${var.aws_region}.amazonses.com"]
}

resource "aws_route53_record" "ses_mail_from_spf" {
  zone_id = data.aws_route53_zone.application.zone_id
  name    = aws_ses_domain_mail_from.application.mail_from_domain
  type    = "TXT"
  ttl     = 300
  records = ["v=spf1 include:amazonses.com ~all"]
}

resource "aws_route53_record" "dmarc" {
  zone_id = data.aws_route53_zone.application.zone_id
  name    = "_dmarc.${var.domain_name}"
  type    = "TXT"
  ttl     = 300
  records = ["v=DMARC1; p=none;"]
}
