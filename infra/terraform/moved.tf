moved {
  from = aws_acm_certificate.application
  to   = module.edge.aws_acm_certificate.application
}

moved {
  from = aws_route53_record.certificate_validation
  to   = module.edge.aws_route53_record.certificate_validation
}

moved {
  from = aws_acm_certificate_validation.application
  to   = module.edge.aws_acm_certificate_validation.application
}

moved {
  from = aws_route53_record.origin
  to   = module.edge.aws_route53_record.origin
}

moved {
  from = aws_cloudfront_distribution.application
  to   = module.edge.aws_cloudfront_distribution.application
}

moved {
  from = aws_route53_record.application_ipv4
  to   = module.edge.aws_route53_record.application_ipv4
}

moved {
  from = aws_route53_record.application_ipv6
  to   = module.edge.aws_route53_record.application_ipv6
}

moved {
  from = aws_route53_record.www_ipv4
  to   = module.edge.aws_route53_record.www_ipv4
}

moved {
  from = aws_route53_record.www_ipv6
  to   = module.edge.aws_route53_record.www_ipv6
}

moved {
  from = aws_ses_domain_identity.application
  to   = module.email.aws_ses_domain_identity.application
}

moved {
  from = aws_sesv2_configuration_set.application
  to   = module.email.aws_sesv2_configuration_set.application
}

moved {
  from = aws_sns_topic.ses_events
  to   = module.email.aws_sns_topic.ses_events
}

moved {
  from = aws_sns_topic_policy.ses_events
  to   = module.email.aws_sns_topic_policy.ses_events
}

moved {
  from = aws_sqs_queue.ses_events
  to   = module.email.aws_sqs_queue.ses_events
}

moved {
  from = aws_sqs_queue_policy.ses_events
  to   = module.email.aws_sqs_queue_policy.ses_events
}

moved {
  from = aws_sns_topic_subscription.ses_events
  to   = module.email.aws_sns_topic_subscription.ses_events
}

moved {
  from = aws_sesv2_configuration_set_event_destination.ses_events
  to   = module.email.aws_sesv2_configuration_set_event_destination.ses_events
}

moved {
  from = aws_route53_record.ses_verification
  to   = module.email.aws_route53_record.ses_verification
}

moved {
  from = aws_ses_domain_identity_verification.application
  to   = module.email.aws_ses_domain_identity_verification.application
}

moved {
  from = aws_ses_domain_dkim.application
  to   = module.email.aws_ses_domain_dkim.application
}

moved {
  from = aws_route53_record.ses_dkim
  to   = module.email.aws_route53_record.ses_dkim
}

moved {
  from = aws_ses_domain_mail_from.application
  to   = module.email.aws_ses_domain_mail_from.application
}

moved {
  from = aws_route53_record.ses_mail_from_mx
  to   = module.email.aws_route53_record.ses_mail_from_mx
}

moved {
  from = aws_route53_record.ses_mail_from_spf
  to   = module.email.aws_route53_record.ses_mail_from_spf
}

moved {
  from = aws_route53_record.dmarc
  to   = module.email.aws_route53_record.dmarc
}

moved {
  from = aws_db_instance.application
  to   = module.database.aws_db_instance.application
}

moved {
  from = module.observability.aws_cloudwatch_log_metric_filter.nginx_5xx
  to   = module.monitoring.aws_cloudwatch_log_metric_filter.nginx_5xx
}

moved {
  from = module.observability.aws_cloudwatch_metric_alarm.nginx_5xx
  to   = module.monitoring.aws_cloudwatch_metric_alarm.nginx_5xx
}

moved {
  from = module.observability.aws_cloudwatch_log_metric_filter.application_errors
  to   = module.monitoring.aws_cloudwatch_log_metric_filter.application_errors
}

moved {
  from = module.observability.aws_cloudwatch_metric_alarm.application_errors
  to   = module.monitoring.aws_cloudwatch_metric_alarm.application_errors
}

moved {
  from = aws_cloudwatch_metric_alarm.instance_status
  to   = module.monitoring.aws_cloudwatch_metric_alarm.instance_status
}

moved {
  from = aws_cloudwatch_metric_alarm.instance_cpu
  to   = module.monitoring.aws_cloudwatch_metric_alarm.instance_cpu
}

moved {
  from = aws_route53_health_check.application
  to   = module.monitoring.aws_route53_health_check.application
}

moved {
  from = aws_sns_topic.uptime_alerts
  to   = module.monitoring.aws_sns_topic.uptime_alerts
}

moved {
  from = aws_sns_topic_subscription.uptime_email
  to   = module.monitoring.aws_sns_topic_subscription.uptime_email
}

moved {
  from = aws_cloudwatch_metric_alarm.uptime
  to   = module.monitoring.aws_cloudwatch_metric_alarm.uptime
}
