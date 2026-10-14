resource "aws_security_group" "load_balancer" {
  count       = var.enable_autoscaling ? 1 : 0
  name        = "${var.name}-alb"
  description = "HTTPS from CloudFront to the application load balancer"
  vpc_id      = var.vpc_id

  ingress {
    from_port       = 443
    to_port         = 443
    protocol        = "tcp"
    prefix_list_ids = [var.cloudfront_prefix_list_id]
  }

  egress {
    from_port       = 80
    to_port         = 80
    protocol        = "tcp"
    security_groups = [aws_security_group.fleet[0].id]
  }
}

resource "aws_security_group" "fleet" {
  count       = var.enable_autoscaling ? 1 : 0
  name        = "${var.name}-fleet"
  description = "Application instances behind the ALB; administration through SSM"
  vpc_id      = var.vpc_id

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_vpc_security_group_ingress_rule" "fleet_http" {
  count                        = var.enable_autoscaling ? 1 : 0
  security_group_id            = aws_security_group.fleet[0].id
  referenced_security_group_id = aws_security_group.load_balancer[0].id
  from_port                    = 80
  to_port                      = 80
  ip_protocol                  = "tcp"
}

resource "aws_lb" "application" {
  count                      = var.enable_autoscaling ? 1 : 0
  name                       = "${var.name}-alb"
  load_balancer_type         = "application"
  subnets                    = var.public_subnet_ids
  security_groups            = [aws_security_group.load_balancer[0].id]
  drop_invalid_header_fields = true
}

resource "aws_lb_target_group" "application" {
  count                = var.enable_autoscaling ? 1 : 0
  name                 = "${var.name}-web"
  port                 = 80
  protocol             = "HTTP"
  vpc_id               = var.vpc_id
  deregistration_delay = 120

  health_check {
    path                = "/up"
    matcher             = "200"
    interval            = 30
    timeout             = 5
    healthy_threshold   = 2
    unhealthy_threshold = 3
  }
}

resource "aws_lb_listener" "https" {
  count             = var.enable_autoscaling ? 1 : 0
  load_balancer_arn = aws_lb.application[0].arn
  port              = 443
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS13-1-2-2021-06"
  certificate_arn   = var.alb_certificate_arn

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.application[0].arn
  }
}

resource "aws_launch_template" "application" {
  count         = var.enable_autoscaling ? 1 : 0
  name          = "${var.name}-fleet"
  image_id      = data.aws_ssm_parameter.ubuntu_2404.value
  instance_type = var.instance_type

  iam_instance_profile {
    name = var.instance_profile_name
  }

  network_interfaces {
    associate_public_ip_address = false
    security_groups             = [aws_security_group.fleet[0].id]
    delete_on_termination       = true
  }

  monitoring {
    enabled = true
  }

  metadata_options {
    http_endpoint               = "enabled"
    http_tokens                 = "required"
    http_put_response_hop_limit = 2
    instance_metadata_tags      = "enabled"
  }

  block_device_mappings {
    device_name = "/dev/sda1"
    ebs {
      volume_type           = "gp3"
      volume_size           = var.root_volume_size
      encrypted             = true
      delete_on_termination = true
    }
  }

  user_data = base64encode(templatefile("${path.module}/templates/autoscaling-user-data.sh.tftpl", {
    compose_version            = var.compose_version
    aws_region                 = var.aws_region
    environment_parameter_path = var.environment_parameter_path
    app_repository_url         = var.app_repository_url
    nginx_repository_url       = var.nginx_repository_url
    redis_endpoint             = aws_elasticache_replication_group.application[0].primary_endpoint_address
    log_group_name             = var.log_group_name
    compose_file_base64        = base64encode(file("${path.module}/../../../../docker/compose.autoscaling.yaml"))
    nginx_config_base64        = base64encode(file("${path.module}/../../../../docker/nginx/autoscaling.conf"))
  }))

  tag_specifications {
    resource_type = "instance"
    tags = {
      Name        = "${var.name}-fleet"
      Application = var.project_name
      Environment = var.environment
      ManagedBy   = "Terraform"
      Release     = var.autoscaling_release_tag
    }
  }

  lifecycle {
    # CI pins releases in new template versions; Terraform owns the infrastructure.
    ignore_changes = [tag_specifications[0].tags["Release"]]
    precondition {
      condition     = can(regex("^[0-9a-f]{40}$", var.autoscaling_release_tag))
      error_message = "Set autoscaling_release_tag to an existing, scaling-ready ECR image's 40-character Git SHA."
    }
    precondition {
      condition     = !startswith(var.instance_type, "t4g.")
      error_message = "The AMI and release images require an x86_64 instance type."
    }
  }
}

resource "aws_autoscaling_group" "application" {
  count                     = var.enable_autoscaling ? 1 : 0
  name                      = "${var.name}-web"
  min_size                  = var.autoscaling_min_size
  max_size                  = var.autoscaling_max_size
  desired_capacity          = var.autoscaling_min_size
  vpc_zone_identifier       = var.application_subnet_ids
  target_group_arns         = [aws_lb_target_group.application[0].arn]
  health_check_type         = "ELB"
  health_check_grace_period = 600
  default_instance_warmup   = 300
  min_elb_capacity          = var.autoscaling_min_size
  wait_for_capacity_timeout = "20m"
  enabled_metrics           = ["GroupDesiredCapacity", "GroupInServiceInstances"]

  launch_template {
    id      = aws_launch_template.application[0].id
    version = aws_launch_template.application[0].latest_version
  }

  instance_maintenance_policy {
    min_healthy_percentage = 100
    max_healthy_percentage = 150
  }

  dynamic "tag" {
    for_each = {
      Application = var.project_name
      Environment = var.environment
      ManagedBy   = "Terraform"
    }
    content {
      key                 = tag.key
      value               = tag.value
      propagate_at_launch = true
    }
  }

  lifecycle {
    # Scaling owns capacity; CI rolls out a numeric template version with rollback.
    ignore_changes = [desired_capacity, launch_template[0].version]
    precondition {
      condition     = length(var.application_subnet_ids) >= 2
      error_message = "The fleet requires private application subnets in at least two availability zones."
    }
    precondition {
      condition     = var.autoscaling_max_size >= var.autoscaling_min_size
      error_message = "autoscaling_max_size must be at least autoscaling_min_size."
    }
  }

  depends_on = [aws_lb_listener.https]
}

resource "aws_autoscaling_policy" "cpu" {
  count                  = var.enable_autoscaling ? 1 : 0
  name                   = "${var.name}-cpu-target"
  autoscaling_group_name = aws_autoscaling_group.application[0].name
  policy_type            = "TargetTrackingScaling"

  target_tracking_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ASGAverageCPUUtilization"
    }
    target_value = var.autoscaling_cpu_target
  }
}
