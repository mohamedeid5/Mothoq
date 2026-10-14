variable "name" {
  description = "Environment-qualified alarm name prefix."
  type        = string
}

variable "project_name" {
  description = "Project name for metrics."
  type        = string
}

variable "environment" {
  description = "Environment name for metrics."
  type        = string
}

variable "instance_id" {
  description = "EC2 instance to monitor."
  type        = string
}

variable "log_group_name" {
  description = "Application log group to inspect for errors."
  type        = string
}

variable "alerts_topic_arn" {
  description = "Regional SNS topic for application and EC2 alarms."
  type        = string
}

variable "domain_name" {
  description = "Public HTTPS domain to check after DNS is ready."
  type        = string
}

variable "alert_email" {
  description = "Optional email recipient for uptime notifications."
  type        = string
  nullable    = true
}

variable "nginx_5xx_threshold" {
  description = "Nginx server errors within five minutes that trigger an alarm."
  type        = number
}
