resource "aws_db_instance" "application" {
  identifier     = "${local.name}-database"
  engine         = "mysql"
  engine_version = "8.4.11"
  instance_class = "db.t3.micro"
  db_name        = var.project_name

  username                    = "mothoq_admin"
  manage_master_user_password = true

  allocated_storage     = 20
  max_allocated_storage = 0
  storage_type          = "gp3"
  storage_encrypted     = true

  db_subnet_group_name   = module.network.database_subnet_group_name
  vpc_security_group_ids = [module.network.database_security_group_id]
  multi_az               = false
  publicly_accessible    = false
  port                   = 3306

  backup_retention_period  = 30
  backup_window            = "01:00-01:30"
  maintenance_window       = "sun:02:00-sun:03:00"
  copy_tags_to_snapshot    = true
  delete_automated_backups = false

  deletion_protection       = true
  skip_final_snapshot       = false
  final_snapshot_identifier = "${local.name}-database-final"

  auto_minor_version_upgrade   = true
  allow_major_version_upgrade  = false
  engine_lifecycle_support     = "open-source-rds-extended-support-disabled"
  apply_immediately            = false
  monitoring_interval          = 0
  performance_insights_enabled = false

  tags = {
    Name = "${local.name}-database"
  }

  lifecycle {
    prevent_destroy = true
  }
}
