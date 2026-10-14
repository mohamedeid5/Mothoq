resource "aws_route53_health_check" "application" {
  fqdn              = var.domain_name
  port              = 443
  type              = "HTTPS"
  resource_path     = "/up"
  request_interval  = 30
  failure_threshold = 3
  enable_sni        = true
  measure_latency   = false

  tags = {
    Name = "${var.name}-uptime"
  }
}

resource "aws_sns_topic" "uptime_alerts" {
  provider = aws.us_east_1
  name     = "${var.name}-uptime-alerts"
}

resource "aws_sns_topic_subscription" "uptime_email" {
  provider = aws.us_east_1
  count    = var.alert_email == null ? 0 : 1

  topic_arn = aws_sns_topic.uptime_alerts.arn
  protocol  = "email"
  endpoint  = var.alert_email
}

resource "aws_cloudwatch_metric_alarm" "uptime" {
  provider = aws.us_east_1

  alarm_name          = "${var.name}-uptime"
  alarm_description   = "Public HTTPS /up health check is unhealthy or monitoring data is missing for two evaluation periods. This checks Laravel boot, not database or queue health."
  namespace           = "AWS/Route53"
  metric_name         = "HealthCheckStatus"
  comparison_operator = "LessThanThreshold"
  threshold           = 1
  period              = 60
  statistic           = "Minimum"
  evaluation_periods  = 2
  datapoints_to_alarm = 2
  treat_missing_data  = "breaching"

  dimensions = {
    HealthCheckId = aws_route53_health_check.application.id
  }

  alarm_actions             = [aws_sns_topic.uptime_alerts.arn]
  ok_actions                = [aws_sns_topic.uptime_alerts.arn]
  insufficient_data_actions = []
}
