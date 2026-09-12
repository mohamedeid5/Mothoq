variable "name" {
  description = "Shared name used for network resources."
  type        = string
}

variable "allowed_http_cidrs" {
  description = "IPv4 networks allowed to reach the public HTTP endpoint."
  type        = list(string)
}
