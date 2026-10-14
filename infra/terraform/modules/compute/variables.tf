variable "name" {
  description = "Shared name used for compute resources."
  type        = string
}

variable "domain_name" {
  description = "Application domain whose origin receives a renewable TLS certificate."
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

variable "enable_autoscaling" {
  type    = bool
  default = false
}

variable "project_name" {
  type    = string
  default = "mothoq"
}

variable "environment" {
  type    = string
  default = "production"
}

variable "vpc_id" {
  type    = string
  default = null
}

variable "public_subnet_ids" {
  type    = list(string)
  default = []
}

variable "private_subnet_ids" {
  type    = list(string)
  default = []
}

variable "cloudfront_prefix_list_id" {
  type    = string
  default = null
}


variable "alb_certificate_arn" {
  type    = string
  default = null
}

variable "autoscaling_release_tag" {
  type    = string
  default = ""
}

variable "autoscaling_min_size" {
  type    = number
  default = 2
}

variable "autoscaling_max_size" {
  type    = number
  default = 4
}

variable "autoscaling_cpu_target" {
  type    = number
  default = 60
}

variable "redis_node_type" {
  type    = string
  default = "cache.t3.micro"
}

variable "aws_account_id" {
  type = string
}

variable "github_actions_role_name" {
  type = string
}

variable "instance_role_arn" {
  type = string
}

variable "alerts_topic_arn" {
  type = string
}

variable "application_subnet_ids" {
  description = "Private application subnets across availability zones, with outbound NAT connectivity."
  type        = list(string)
  default     = []
}
