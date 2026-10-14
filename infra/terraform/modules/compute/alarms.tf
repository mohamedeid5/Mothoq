resource "aws_cloudwatch_metric_alarm" "fleet_unhealthy" {
  count               = var.enable_autoscaling ? 1 : 0
  alarm_name          = "${var.name}-fleet-unhealthy"
  alarm_description   = "The ALB has an unhealthy application target for two minutes."
  namespace           = "AWS/ApplicationELB"
  metric_name         = "UnHealthyHostCount"
  comparison_operator = "GreaterThanOrEqualToThreshold"
  threshold           = 1
  period              = 60
  statistic           = "Maximum"
  evaluation_periods  = 2
  treat_missing_data  = "notBreaching"
  dimensions = {
    LoadBalancer = aws_lb.application[0].arn_suffix
    TargetGroup  = aws_lb_target_group.application[0].arn_suffix
  }
  alarm_actions = [var.alerts_topic_arn]
  ok_actions    = [var.alerts_topic_arn]
}

resource "aws_cloudwatch_metric_alarm" "fleet_capacity" {
  count               = var.enable_autoscaling ? 1 : 0
  alarm_name          = "${var.name}-fleet-capacity"
  alarm_description   = "Healthy application capacity is below the configured minimum."
  namespace           = "AWS/ApplicationELB"
  metric_name         = "HealthyHostCount"
  comparison_operator = "LessThanThreshold"
  threshold           = var.autoscaling_min_size
  period              = 60
  statistic           = "Minimum"
  evaluation_periods  = 3
  treat_missing_data  = "breaching"
  dimensions = {
    LoadBalancer = aws_lb.application[0].arn_suffix
    TargetGroup  = aws_lb_target_group.application[0].arn_suffix
  }
  alarm_actions = [var.alerts_topic_arn]
  ok_actions    = [var.alerts_topic_arn]
}
