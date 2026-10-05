output "app_repository_arn" {
  description = "ARN of the application ECR repository."
  value       = aws_ecr_repository.app.arn
}

output "app_repository_url" {
  description = "URL of the application ECR repository."
  value       = aws_ecr_repository.app.repository_url
}

output "nginx_repository_arn" {
  description = "ARN of the Nginx ECR repository."
  value       = aws_ecr_repository.nginx.arn
}

output "nginx_repository_url" {
  description = "URL of the Nginx ECR repository."
  value       = aws_ecr_repository.nginx.repository_url
}

output "uploads_bucket_name" {
  description = "Private bucket name for future Laravel S3 configuration."
  value       = aws_s3_bucket.uploads.id
}

output "uploads_bucket_arn" {
  description = "ARN of the private application uploads bucket."
  value       = aws_s3_bucket.uploads.arn
}
