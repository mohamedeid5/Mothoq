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
  records = [var.origin_ip]
}

resource "aws_cloudfront_distribution" "application" {
  enabled         = true
  is_ipv6_enabled = true
  aliases         = [var.domain_name, "www.${var.domain_name}"]
  comment         = "Mothoq production application"
  http_version    = "http2and3"
  price_class     = "PriceClass_100"

  origin {
    domain_name = var.route_autoscaling_traffic ? aws_route53_record.load_balancer[0].fqdn : aws_route53_record.origin.fqdn
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
