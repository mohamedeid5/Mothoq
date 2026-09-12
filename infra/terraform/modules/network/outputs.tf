output "public_subnet_id" {
  description = "ID of the public application subnet."
  value       = aws_subnet.public.id
}

output "web_security_group_id" {
  description = "ID of the security group allowing public HTTP traffic."
  value       = aws_security_group.web.id
}
