variable "domain_name" {
  description = "Public domain served by CloudFront."
  type        = string
}

variable "origin_ip" {
  description = "Public IPv4 address of the application origin."
  type        = string
}

variable "enable_autoscaling" {
  type    = bool
  default = false
}
variable "route_autoscaling_traffic" {
  type    = bool
  default = false
}
variable "load_balancer_dns_name" {
  type    = string
  default = null
}
variable "load_balancer_zone_id" {
  type    = string
  default = null
}
