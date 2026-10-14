output "public_subnet_id" {
  description = "ID of the public application subnet."
  value       = aws_subnet.public.id
}

output "web_security_group_id" {
  description = "ID of the security group allowing public HTTP traffic."
  value       = aws_security_group.web.id
}

output "vpc_id" {
  description = "ID of the application VPC."
  value       = aws_vpc.main.id
}

output "public_subnet_ids" {
  description = "Public subnet IDs across two availability zones for a load balancer."
  value       = [aws_subnet.public.id, aws_subnet.public_secondary.id]
}

output "private_subnet_ids" {
  description = "Private subnet IDs across two availability zones for the database."
  value       = [aws_subnet.private_primary.id, aws_subnet.private_secondary.id]
}

output "database_subnet_group_name" {
  description = "Name of the private subnet group for RDS."
  value       = aws_db_subnet_group.database.name
}

output "database_security_group_id" {
  description = "ID of the security group allowing MySQL from the application."
  value       = aws_security_group.database.id
}

output "primary_availability_zone" {
  description = "Availability zone of the existing application subnet."
  value       = aws_subnet.public.availability_zone
}

output "cloudfront_prefix_list_id" {
  value = data.aws_ec2_managed_prefix_list.cloudfront.id
}

output "application_subnet_ids" {
  description = "Private application subnets with working outbound routes before instances bootstrap."
  value       = [for subnet in aws_subnet.application : subnet.id]
  depends_on  = [aws_route.application_internet, aws_route_table_association.application, aws_vpc_endpoint.s3]
}
