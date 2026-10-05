variable "project_name" {
  description = "Project name used as the ECR repository prefix."
  type        = string
}

variable "uploads_bucket_name" {
  description = "Globally unique name of the private application uploads bucket."
  type        = string
}

variable "instance_role_name" {
  description = "Existing EC2 role allowed to manage service-center images."
  type        = string
}

variable "force_delete_repositories" {
  description = "Whether non-empty ECR repositories may be deleted during teardown."
  type        = bool
  default     = false
}
