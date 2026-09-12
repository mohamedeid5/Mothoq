output "instance_id" {
  description = "ID of the application EC2 instance."
  value       = aws_instance.app.id
}

output "public_ip" {
  description = "Public IPv4 address of the application instance."
  value       = try(data.aws_eip.existing[0].public_ip, aws_instance.app.public_ip)
}
