resource "aws_security_group" "redis" {
  count       = var.enable_autoscaling ? 1 : 0
  name        = "${var.name}-redis"
  description = "Shared Redis access from application instances"
  vpc_id      = var.vpc_id

  ingress {
    from_port       = 6379
    to_port         = 6379
    protocol        = "tcp"
    security_groups = [aws_security_group.fleet[0].id, var.security_group_id]
  }
  egress = []
}

resource "aws_elasticache_subnet_group" "application" {
  count      = var.enable_autoscaling ? 1 : 0
  name       = "${var.name}-redis"
  subnet_ids = var.private_subnet_ids
}

resource "aws_elasticache_parameter_group" "application" {
  count  = var.enable_autoscaling ? 1 : 0
  name   = "${var.name}-redis"
  family = "redis7"

  parameter {
    name  = "maxmemory-policy"
    value = "noeviction"
  }
}

resource "aws_elasticache_replication_group" "application" {
  count                      = var.enable_autoscaling ? 1 : 0
  replication_group_id       = "${var.name}-redis"
  description                = "Shared sessions, cache, locks and Horizon queues"
  engine                     = "redis"
  engine_version             = "7.1"
  node_type                  = var.redis_node_type
  num_cache_clusters         = 2
  automatic_failover_enabled = true
  multi_az_enabled           = true
  at_rest_encryption_enabled = true
  transit_encryption_enabled = true
  parameter_group_name       = aws_elasticache_parameter_group.application[0].name
  subnet_group_name          = aws_elasticache_subnet_group.application[0].name
  security_group_ids         = [aws_security_group.redis[0].id]
  snapshot_retention_limit   = 7
  snapshot_window            = "02:00-03:00"
}
