variable "name" {
  description = "Shared name used for network resources."
  type        = string
}

variable "allowed_http_cidrs" {
  description = "IPv4 networks allowed to reach the public HTTP endpoint."
  type        = list(string)
}

variable "additional_database_security_group_ids" {
  type    = list(string)
  default = []
}

variable "enable_private_application_network" {
  description = "Create private application subnets, one NAT per AZ, and an S3 gateway endpoint with the fleet."
  type        = bool
  default     = false
}

variable "aws_region" {
  description = "Region used for the S3 gateway endpoint."
  type        = string
}

variable "legacy_ssh_cidrs" {
  description = "Existing administrator SSH networks retained on the legacy host during migration."
  type        = list(string)
  default     = []
}
