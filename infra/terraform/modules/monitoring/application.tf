resource "aws_cloudwatch_log_metric_filter" "nginx_5xx" {
  name           = "${var.project_name}-${var.environment}-nginx-5xx"
  pattern        = "[ip, identity, user, timestamp, request, status_code = 5*, size, referrer, agent, forwarded]"
  log_group_name = var.log_group_name

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

  alarm_actions             = [var.alerts_topic_arn]
  ok_actions                = [var.alerts_topic_arn]
  insufficient_data_actions = []

  depends_on = [aws_cloudwatch_log_metric_filter.nginx_5xx]
}

resource "aws_cloudwatch_log_metric_filter" "application_errors" {
  name           = "${var.project_name}-${var.environment}-application-errors"
  pattern        = "%\\.ERROR:|\\.CRITICAL:|\\.ALERT:|\\.EMERGENCY:%"
  log_group_name = var.log_group_name

  metric_transformation {
    name          = "ApplicationErrorCount"
    namespace     = local.metric_namespace
    value         = "1"
    default_value = "0"
    unit          = "Count"
  }
}

resource "aws_cloudwatch_metric_alarm" "application_errors" {
  alarm_name          = "${var.project_name}-${var.environment}-application-errors"
  alarm_description   = "Laravel logged an error or higher severity event within five minutes, including reported queue exceptions. Inspect the application and Horizon log streams."
  comparison_operator = "GreaterThanOrEqualToThreshold"
  evaluation_periods  = 1
  datapoints_to_alarm = 1
  threshold           = 1
  metric_name         = "ApplicationErrorCount"
  namespace           = local.metric_namespace
  period              = 300
  statistic           = "Sum"
  treat_missing_data  = "notBreaching"

  alarm_actions             = [var.alerts_topic_arn]
  ok_actions                = [var.alerts_topic_arn]
  insufficient_data_actions = []

  depends_on = [aws_cloudwatch_log_metric_filter.application_errors]
}
