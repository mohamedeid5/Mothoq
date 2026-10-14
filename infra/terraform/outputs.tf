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
  value       = module.monitoring.nginx_5xx_alarm_name
}

output "uptime_health_check_id" {
  description = "Route 53 health check monitoring the public HTTPS /up endpoint."
  value       = module.monitoring.uptime_health_check_id
}

output "uptime_alarm_name" {
  description = "Uptime CloudWatch alarm in us-east-1."
  value       = module.monitoring.uptime_alarm_name
}

output "uptime_alerts_topic_arn" {
  description = "SNS topic in us-east-1; its email subscription requires confirmation."
  value       = module.monitoring.uptime_alerts_topic_arn
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
  value       = module.email.identity_arn
}

output "ses_configuration_set_name" {
  description = "Configuration set Laravel should select when sending SES email."
  value       = module.email.configuration_set_name
}

output "ses_events_topic_arn" {
  description = "SNS topic receiving SES delivery and feedback events."
  value       = module.email.events_topic_arn
}

output "ses_events_queue_url" {
  description = "SQS queue URL for the SES event consumer."
  value       = module.email.events_queue_url
}

output "ses_events_queue_arn" {
  description = "SQS queue ARN for scoped consumer permissions."
  value       = module.email.events_queue_arn
}

output "uploads_bucket_name" {
  description = "Private uploads bucket to use as AWS_BUCKET when enabling S3 in Laravel."
  value       = module.storage.uploads_bucket_name
}

output "uploads_bucket_arn" {
  description = "ARN of the private application uploads bucket."
  value       = module.storage.uploads_bucket_arn
}

output "database_address" {
  description = "Private RDS hostname; the application is not switched to it automatically."
  value       = module.database.address
}

output "database_identifier" {
  description = "Identifier of the production RDS instance."
  value       = module.database.identifier
}

output "database_master_secret_arn" {
  description = "Secrets Manager ARN for the RDS-managed administrator credentials, not their value."
  value       = module.database.master_secret_arn
}

output "autoscaling_group_name" {
  value = module.compute.autoscaling_group_name
}
output "load_balancer_dns_name" {
  value = module.compute.load_balancer_dns_name
}
output "redis_endpoint" {
  value = module.compute.redis_endpoint
}
