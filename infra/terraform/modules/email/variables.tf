variable "name" {
  description = "Environment-qualified name for the SES configuration set."
  type        = string
}

variable "project_name" {
  description = "Project name used for email event resources."
  type        = string
}

variable "domain_name" {
  description = "Domain authorized to send transactional email."
  type        = string
}

variable "zone_id" {
  description = "Public hosted zone for email verification records."
  type        = string
}

variable "aws_region" {
  description = "AWS region sending email."
  type        = string
}

variable "aws_account_id" {
  description = "AWS account allowed to publish SES events."
  type        = string
}
