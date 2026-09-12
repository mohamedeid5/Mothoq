variable "aws_region" {
  description = "AWS region in which Mothoq will run."
  type        = string
  default     = "eu-north-1"
}

variable "project_name" {
  description = "Short name used to prefix AWS resources."
  type        = string
  default     = "mothoq"
}

variable "environment" {
  description = "Deployment environment name."
  type        = string
  default     = "production"
}

variable "instance_type" {
  description = "EC2 instance type. Keep this eligible for your account's Free Tier."
  type        = string
  default     = "t3.micro"

  validation {
    condition     = contains(["t3.micro", "t3.small", "t4g.micro", "t4g.small", "c7i-flex.large", "m7i-flex.large"], var.instance_type)
    error_message = "Choose an instance type currently listed as Free Tier eligible for this account."
  }
}

variable "root_volume_size" {
  description = "Size in GiB of the encrypted gp3 root volume."
  type        = number
  default     = 20

  validation {
    condition     = var.root_volume_size >= 8 && var.root_volume_size <= 30
    error_message = "Root volume size must be between 8 and 30 GiB."
  }
}

variable "allowed_http_cidrs" {
  description = "IPv4 networks allowed to reach the public HTTP endpoint."
  type        = list(string)
  default     = ["0.0.0.0/0"]
}

variable "existing_eip_allocation_id" {
  description = "Optional existing Elastic IP allocation to associate with the instance."
  type        = string
  default     = null
  nullable    = true
}

variable "terraform_operator_user" {
  description = "IAM user that runs Terraform and pushes release images."
  type        = string
  default     = "mohamed"
}

variable "force_delete_repositories" {
  description = "Allow Terraform to delete non-empty ECR repositories during an explicit teardown."
  type        = bool
  default     = false
}

variable "github_repository_owner" {
  description = "GitHub account that owns the repository allowed to deploy."
  type        = string
  default     = "mohamedeid5"
}

variable "github_repository_owner_id" {
  description = "Immutable numeric GitHub ID of the repository owner."
  type        = string
  default     = "19219248"
}

variable "github_repository_name" {
  description = "GitHub repository allowed to deploy."
  type        = string
  default     = "Mothoq"
}

variable "github_repository_id" {
  description = "Immutable numeric GitHub repository ID."
  type        = string
  default     = "1364337962"
}

variable "github_deployment_branch" {
  description = "Only this GitHub branch may assume the production deployment role."
  type        = string
  default     = "main"
}
