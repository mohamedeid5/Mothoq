resource "aws_ses_domain_identity" "application" {
  domain = var.domain_name
}

resource "aws_sesv2_configuration_set" "application" {
  configuration_set_name = var.name

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
        StringEquals = { "aws:SourceAccount" = var.aws_account_id }
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
  zone_id = var.zone_id
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

  zone_id = var.zone_id
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
  zone_id = var.zone_id
  name    = aws_ses_domain_mail_from.application.mail_from_domain
  type    = "MX"
  ttl     = 300
  records = ["10 feedback-smtp.${var.aws_region}.amazonses.com"]
}

resource "aws_route53_record" "ses_mail_from_spf" {
  zone_id = var.zone_id
  name    = aws_ses_domain_mail_from.application.mail_from_domain
  type    = "TXT"
  ttl     = 300
  records = ["v=spf1 include:amazonses.com ~all"]
}

resource "aws_route53_record" "dmarc" {
  zone_id = var.zone_id
  name    = "_dmarc.${var.domain_name}"
  type    = "TXT"
  ttl     = 300
  records = ["v=DMARC1; p=none;"]
}
