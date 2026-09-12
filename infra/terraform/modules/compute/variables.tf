variable "name" {
  description = "Shared name used for compute resources."
  type        = string
}

variable "aws_region" {
  description = "AWS region containing the deployment resources."
  type        = string
}

variable "instance_type" {
  description = "EC2 instance type used by the application host."
  type        = string
}

variable "root_volume_size" {
  description = "Size in GiB of the encrypted root volume."
  type        = number
}

variable "subnet_id" {
  description = "Subnet in which the application instance runs."
  type        = string
}

variable "security_group_id" {
  description = "Security group attached to the application instance."
  type        = string
}

variable "instance_profile_name" {
  description = "IAM instance profile attached to the application instance."
  type        = string
}

variable "existing_eip_allocation_id" {
  description = "Optional existing Elastic IP allocation associated with the instance."
  type        = string
  nullable    = true
}

variable "compose_version" {
  description = "Docker Compose release installed by the bootstrap script."
  type        = string
}

variable "app_repository_url" {
  description = "URL of the application ECR repository."
  type        = string
}

variable "nginx_repository_url" {
  description = "URL of the Nginx ECR repository."
  type        = string
}

variable "log_group_name" {
  description = "CloudWatch log group used by the application containers."
  type        = string
}

variable "environment_parameter_path" {
  description = "SSM parameter path containing the production environment."
  type        = string
}
