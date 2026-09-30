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
