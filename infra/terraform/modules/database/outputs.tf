output "address" {
  description = "Private database hostname."
  value       = aws_db_instance.application.address
}

output "identifier" {
  description = "RDS instance identifier."
  value       = aws_db_instance.application.identifier
}

output "master_secret_arn" {
  description = "ARN of the RDS-managed administrator secret."
  value       = aws_db_instance.application.master_user_secret[0].secret_arn
}
