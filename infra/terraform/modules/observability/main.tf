resource "aws_cloudwatch_log_group" "application" {
  name              = "/${var.project_name}/${var.environment}"
  retention_in_days = var.retention_in_days
}

resource "aws_sns_topic" "alerts" {
  name = "${var.project_name}-${var.environment}-alerts"
}

resource "aws_sns_topic_subscription" "email" {
  count = var.alert_email == null ? 0 : 1

  topic_arn = aws_sns_topic.alerts.arn
  protocol  = "email"
  endpoint  = var.alert_email
}
