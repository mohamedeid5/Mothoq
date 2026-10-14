output "instance_id" {
  description = "ID of the application EC2 instance."
  value       = aws_instance.app.id
}

output "public_ip" {
  description = "Public IPv4 address of the application instance."
  value       = try(data.aws_eip.existing[0].public_ip, aws_instance.app.public_ip)
}

output "autoscaling_group_name" {
  value = try(aws_autoscaling_group.application[0].name, null)
}

output "load_balancer_dns_name" {
  value = try(aws_lb.application[0].dns_name, null)
}

output "load_balancer_zone_id" {
  value = try(aws_lb.application[0].zone_id, null)
}

output "redis_endpoint" {
  value = try(aws_elasticache_replication_group.application[0].primary_endpoint_address, null)
}

output "fleet_security_group_ids" {
  value = aws_security_group.fleet[*].id
}

output "fleet_launch_template_arn" {
  value = try(aws_launch_template.application[0].arn, null)
}
