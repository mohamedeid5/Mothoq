variable "project_name" {
  description = "Project name used in the application log group path."
  type        = string
}

variable "environment" {
  description = "Environment name used in the application log group path."
  type        = string
}

variable "retention_in_days" {
  description = "Number of days CloudWatch retains application logs."
  type        = number
}

variable "alert_email" {
  description = "Optional email address subscribed to production alerts."
  type        = string
  nullable    = true
}
