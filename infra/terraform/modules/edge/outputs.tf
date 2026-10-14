output "zone_id" {
  description = "Hosted zone containing the application and email records."
  value       = data.aws_route53_zone.application.zone_id
}

output "domain_name" {
  description = "Application domain, available after the public IPv4 record is configured."
  value       = var.domain_name
  depends_on  = [aws_route53_record.application_ipv4]
}

output "alb_certificate_arn" {
  value = try(aws_acm_certificate_validation.load_balancer[0].certificate_arn, null)
}
