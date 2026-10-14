variable "name" {
  description = "Environment-qualified database resource name."
  type        = string
}

variable "project_name" {
  description = "Initial database name."
  type        = string
}

variable "subnet_group_name" {
  description = "Private subnet group provided by the network module."
  type        = string
}

variable "security_group_id" {
  description = "Security group allowing application access to MySQL."
  type        = string
}
