variable "project_name" {
  description = "Project name used as the ECR repository prefix."
  type        = string
}

variable "force_delete_repositories" {
  description = "Whether non-empty ECR repositories may be deleted during teardown."
  type        = bool
  default     = false
}
