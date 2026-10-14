output "nginx_5xx_alarm_name" {
  description = "Nginx error alarm name."
  value       = aws_cloudwatch_metric_alarm.nginx_5xx.alarm_name
}

output "uptime_health_check_id" {
  description = "Public HTTPS health check ID."
  value       = aws_route53_health_check.application.id
}

output "uptime_alarm_name" {
  description = "Uptime alarm in us-east-1."
  value       = aws_cloudwatch_metric_alarm.uptime.alarm_name
}

output "uptime_alerts_topic_arn" {
  description = "SNS topic in us-east-1 for uptime notifications."
  value       = aws_sns_topic.uptime_alerts.arn
}
