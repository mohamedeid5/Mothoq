resource "aws_iam_role_policy" "fleet_deployment" {
  name = "${var.name}-fleet-deployment"
  role = var.github_actions_role_name

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect   = "Allow"
        Action   = ["autoscaling:DescribeAutoScalingGroups", "autoscaling:DescribeInstanceRefreshes", "ec2:DescribeLaunchTemplateVersions", "ec2:DescribeLaunchTemplates"]
        Resource = "*"
      },
      {
        Effect   = "Allow"
        Action   = "autoscaling:StartInstanceRefresh"
        Resource = "arn:aws:autoscaling:${var.aws_region}:${var.aws_account_id}:autoScalingGroup:*:autoScalingGroupName/${var.name}-web"
      },
      {
        Effect   = "Allow"
        Action   = "ec2:CreateLaunchTemplateVersion"
        Resource = aws_launch_template.application[0].arn
        Condition = { StringEquals = {
          "aws:ResourceTag/Application" = var.project_name
          "aws:ResourceTag/Environment" = var.environment
        } }
      },
      {
        Effect    = "Allow"
        Action    = "iam:PassRole"
        Resource  = var.instance_role_arn
        Condition = { StringEquals = { "iam:PassedToService" = "ec2.amazonaws.com" } }
      },
      {
        Effect   = "Allow"
        Action   = "ec2:RunInstances"
        Resource = "*"
        Condition = {
          ArnEquals    = { "ec2:LaunchTemplate" = aws_launch_template.application[0].arn }
          StringEquals = { "aws:RequestedRegion" = var.aws_region }
        }
      },
      {
        Effect    = "Allow"
        Action    = "ec2:CreateTags"
        Resource  = "arn:aws:ec2:${var.aws_region}:${var.aws_account_id}:instance/*"
        Condition = { StringEquals = { "ec2:CreateAction" = "RunInstances" } }
      }
    ]
  })
  count = var.enable_autoscaling ? 1 : 0
}
