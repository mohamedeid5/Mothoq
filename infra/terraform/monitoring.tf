resource "aws_cloudwatch_metric_alarm" "instance_status" {
  alarm_name          = "${local.name}-instance-status"
  alarm_description   = "EC2 failed a status check for two consecutive minutes."
  namespace           = "AWS/EC2"
  metric_name         = "StatusCheckFailed"
  comparison_operator = "GreaterThanOrEqualToThreshold"
  threshold           = 1
  period              = 60
  statistic           = "Maximum"
  evaluation_periods  = 2
  datapoints_to_alarm = 2
  treat_missing_data  = "missing"

  dimensions = {
    InstanceId = module.compute.instance_id
  }

  alarm_actions             = [module.observability.alerts_topic_arn]
  ok_actions                = [module.observability.alerts_topic_arn]
  insufficient_data_actions = []
}

resource "aws_cloudwatch_metric_alarm" "instance_cpu" {
  alarm_name          = "${local.name}-instance-cpu"
  alarm_description   = "EC2 average CPU utilization is at least 85 percent for fifteen minutes."
  namespace           = "AWS/EC2"
  metric_name         = "CPUUtilization"
  comparison_operator = "GreaterThanOrEqualToThreshold"
  threshold           = 85
  period              = 300
  statistic           = "Average"
  evaluation_periods  = 3
  datapoints_to_alarm = 3
  treat_missing_data  = "missing"

  dimensions = {
    InstanceId = module.compute.instance_id
  }

  alarm_actions             = [module.observability.alerts_topic_arn]
  ok_actions                = [module.observability.alerts_topic_arn]
  insufficient_data_actions = []
}
