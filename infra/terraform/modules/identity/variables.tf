variable "name" {
  description = "Shared name used for IAM resources."
  type        = string
}

variable "project_name" {
  description = "Project tag value used to scope deployment commands."
  type        = string
}

variable "environment" {
  description = "Environment tag value used to scope deployment commands."
  type        = string
}

variable "aws_region" {
  description = "AWS region containing the deployment resources."
  type        = string
}

variable "aws_account_id" {
  description = "AWS account ID containing the deployment resources."
  type        = string
}

variable "terraform_operator_user" {
  description = "IAM user that runs Terraform and pushes release images."
  type        = string
}

variable "app_repository_arn" {
  description = "ARN of the application ECR repository."
  type        = string
}

variable "nginx_repository_arn" {
  description = "ARN of the Nginx ECR repository."
  type        = string
}

variable "log_group_arn" {
  description = "ARN of the application CloudWatch log group."
  type        = string
}

variable "environment_parameter_arn" {
  description = "ARN of the SSM parameter containing the production environment."
  type        = string
}

variable "github_oidc_subject" {
  description = "Exact GitHub OIDC subject allowed to assume the deployment role."
  type        = string
}
