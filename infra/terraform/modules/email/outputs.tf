output "identity_arn" {
  description = "Verified SES identity ARN."
  value       = aws_ses_domain_identity.application.arn
}

output "configuration_set_name" {
  description = "SES configuration set for application email."
  value       = aws_sesv2_configuration_set.application.configuration_set_name
}

output "events_topic_arn" {
  description = "SNS topic receiving SES events."
  value       = aws_sns_topic.ses_events.arn
}

output "events_queue_url" {
  description = "SQS URL for email event consumers."
  value       = aws_sqs_queue.ses_events.url
}

output "events_queue_arn" {
  description = "SQS ARN for consumer permissions."
  value       = aws_sqs_queue.ses_events.arn
}
