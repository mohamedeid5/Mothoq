output "instance_id" {
  description = "EC2 instance ID used with Session Manager and deployment commands."
  value       = module.compute.instance_id
}

output "public_ip" {
  description = "Current public IPv4 address. It changes after a stop/start unless an Elastic IP is attached."
  value       = module.compute.public_ip
}

output "application_url" {
  description = "Temporary HTTP URL before a domain and TLS are configured."
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
