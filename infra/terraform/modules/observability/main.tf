resource "aws_cloudwatch_log_group" "application" {
  name              = "/${var.project_name}/${var.environment}"
  retention_in_days = var.retention_in_days
}

locals {
  metric_namespace = "${var.project_name}/${var.environment}"
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

resource "aws_cloudwatch_log_metric_filter" "nginx_5xx" {
  name           = "${var.project_name}-${var.environment}-nginx-5xx"
  pattern        = "[ip, identity, user, timestamp, request, status_code = 5*, size, referrer, agent, forwarded]"
  log_group_name = aws_cloudwatch_log_group.application.name

  metric_transformation {
    name          = "Nginx5xxCount"
    namespace     = local.metric_namespace
    value         = "1"
    default_value = "0"
    unit          = "Count"
  }
}

resource "aws_cloudwatch_metric_alarm" "nginx_5xx" {
  alarm_name          = "${var.project_name}-${var.environment}-nginx-5xx"
  alarm_description   = "Nginx returned at least ${var.nginx_5xx_threshold} server errors within five minutes."
  comparison_operator = "GreaterThanOrEqualToThreshold"
  evaluation_periods  = 1
  datapoints_to_alarm = 1
  threshold           = var.nginx_5xx_threshold
  metric_name         = "Nginx5xxCount"
  namespace           = local.metric_namespace
  period              = 300
  statistic           = "Sum"
  treat_missing_data  = "notBreaching"

  alarm_actions             = [aws_sns_topic.alerts.arn]
  ok_actions                = [aws_sns_topic.alerts.arn]
  insufficient_data_actions = []

  depends_on = [aws_cloudwatch_log_metric_filter.nginx_5xx]
}
