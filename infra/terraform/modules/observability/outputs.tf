output "log_group_arn" {
  description = "ARN of the application CloudWatch log group."
  value       = aws_cloudwatch_log_group.application.arn
}

output "log_group_name" {
  description = "Name of the application CloudWatch log group."
  value       = aws_cloudwatch_log_group.application.name
}

output "alerts_topic_arn" {
  description = "ARN of the SNS topic receiving production alarms."
  value       = aws_sns_topic.alerts.arn
}
