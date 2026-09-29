resource "aws_cloudwatch_log_group" "application" {
  name              = "/${var.project_name}/${var.environment}"
  retention_in_days = var.retention_in_days
}
