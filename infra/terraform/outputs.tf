output "instance_id" {
  description = "EC2 instance ID used with Session Manager and deployment commands."
  value       = module.compute.instance_id
}

output "public_ip" {
  description = "Current public IPv4 address. It changes after a stop/start unless an Elastic IP is attached."
  value       = module.compute.public_ip
}

output "application_url" {
  description = "Canonical HTTPS URL served through CloudFront."
  value       = "https://${var.domain_name}"
}

output "origin_url" {
  description = "Direct HTTP origin URL used for infrastructure diagnostics."
  value       = "http://${module.compute.public_ip}"
}

output "app_repository_url" {
  description = "ECR repository for the PHP-FPM application image."
  value       = module.storage.app_repository_url
}

output "nginx_repository_url" {
  description = "ECR repository for the Nginx image."
  value       = module.storage.nginx_repository_url
}

output "cloudwatch_log_group_name" {
  description = "CloudWatch Logs group receiving production container stdout and stderr."
  value       = module.observability.log_group_name
}

output "alerts_topic_arn" {
  description = "SNS topic that receives production CloudWatch alarms."
  value       = module.observability.alerts_topic_arn
}

output "nginx_5xx_alarm_name" {
  description = "CloudWatch alarm monitoring Nginx 5xx responses."
  value       = module.observability.nginx_5xx_alarm_name
}

output "uptime_health_check_id" {
  description = "Route 53 health check monitoring the public HTTPS /up endpoint."
  value       = aws_route53_health_check.application.id
}

output "uptime_alarm_name" {
  description = "Uptime CloudWatch alarm in us-east-1."
  value       = aws_cloudwatch_metric_alarm.uptime.alarm_name
}

output "uptime_alerts_topic_arn" {
  description = "SNS topic in us-east-1; its email subscription requires confirmation."
  value       = aws_sns_topic.uptime_alerts.arn
}

output "production_env_parameter" {
  description = "Create this SecureString parameter separately so its secret never enters Terraform state."
  value       = local.parameter_path
}

output "session_manager_command" {
  description = "Command for opening a shell without exposing SSH."
  value       = "aws ssm start-session --target ${module.compute.instance_id} --region ${var.aws_region}"
}

output "github_actions_role_arn" {
  description = "IAM role used by the GitHub Actions deployment workflow."
  value       = module.identity.github_actions_role_arn
}

output "ses_identity_arn" {
  description = "Verified SES identity used for transactional email."
  value       = aws_ses_domain_identity.application.arn
}

output "ses_configuration_set_name" {
  description = "Configuration set Laravel should select when sending SES email."
  value       = aws_sesv2_configuration_set.application.configuration_set_name
}

output "ses_events_topic_arn" {
  description = "SNS topic receiving SES delivery and feedback events."
  value       = aws_sns_topic.ses_events.arn
}

output "ses_events_queue_url" {
  description = "SQS queue URL for the SES event consumer."
  value       = aws_sqs_queue.ses_events.url
}

output "ses_events_queue_arn" {
  description = "SQS queue ARN for scoped consumer permissions."
  value       = aws_sqs_queue.ses_events.arn
}

output "uploads_bucket_name" {
  description = "Private uploads bucket to use as AWS_BUCKET when enabling S3 in Laravel."
  value       = module.storage.uploads_bucket_name
}

output "uploads_bucket_arn" {
  description = "ARN of the private application uploads bucket."
  value       = module.storage.uploads_bucket_arn
}
