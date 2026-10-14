module "identity" {
  source = "./modules/identity"

  name                      = local.name
  project_name              = var.project_name
  environment               = var.environment
  aws_region                = var.aws_region
  aws_account_id            = data.aws_caller_identity.current.account_id
  terraform_operator_user   = var.terraform_operator_user
  app_repository_arn        = local.app_repository_arn
  nginx_repository_arn      = local.nginx_repository_arn
  log_group_arn             = local.log_group_arn
  alert_topic_arn           = local.alert_topic_arn
  alarm_arn_prefix          = local.alarm_arn_prefix
  environment_parameter_arn = local.environment_parameter_arn
  github_oidc_subject       = local.github_oidc_subject
  ses_from_address          = "noreply@${var.domain_name}"
}

module "network" {
  source                             = "./modules/network"
  aws_region                         = var.aws_region
  enable_private_application_network = var.enable_autoscaling

  name                                   = local.name
  allowed_http_cidrs                     = var.allowed_http_cidrs
  additional_database_security_group_ids = module.compute.fleet_security_group_ids
}

module "storage" {
  source = "./modules/storage"

  project_name              = var.project_name
  force_delete_repositories = var.force_delete_repositories
  uploads_bucket_name       = "${local.name}-uploads-${data.aws_caller_identity.current.account_id}-${var.aws_region}"
  instance_role_name        = module.identity.instance_role_name

  depends_on = [module.identity]
}

module "observability" {
  source = "./modules/observability"

  project_name      = var.project_name
  environment       = var.environment
  retention_in_days = var.log_retention_days
  alert_email       = var.alert_email

  depends_on = [module.identity]
}

module "compute" {
  source                   = "./modules/compute"
  alerts_topic_arn         = module.observability.alerts_topic_arn
  aws_account_id           = data.aws_caller_identity.current.account_id
  github_actions_role_name = module.identity.github_actions_role_name
  instance_role_arn        = module.identity.instance_role_arn

  enable_autoscaling         = var.enable_autoscaling
  project_name               = var.project_name
  environment                = var.environment
  vpc_id                     = module.network.vpc_id
  public_subnet_ids          = module.network.public_subnet_ids
  application_subnet_ids     = module.network.application_subnet_ids
  private_subnet_ids         = module.network.private_subnet_ids
  cloudfront_prefix_list_id  = module.network.cloudfront_prefix_list_id
  alb_certificate_arn        = module.edge.alb_certificate_arn
  autoscaling_release_tag    = var.autoscaling_release_tag
  autoscaling_min_size       = var.autoscaling_min_size
  autoscaling_max_size       = var.autoscaling_max_size
  autoscaling_cpu_target     = var.autoscaling_cpu_target
  redis_node_type            = var.redis_node_type
  name                       = local.name
  domain_name                = var.domain_name
  aws_region                 = var.aws_region
  instance_type              = var.instance_type
  root_volume_size           = var.root_volume_size
  subnet_id                  = module.network.public_subnet_id
  security_group_id          = module.network.web_security_group_id
  instance_profile_name      = module.identity.instance_profile_name
  existing_eip_allocation_id = var.existing_eip_allocation_id
  compose_version            = local.compose_version
  app_repository_url         = module.storage.app_repository_url
  nginx_repository_url       = module.storage.nginx_repository_url
  log_group_name             = module.observability.log_group_name
  environment_parameter_path = local.parameter_path
}

module "edge" {
  source = "./modules/edge"

  providers = {
    aws           = aws
    aws.us_east_1 = aws.us_east_1
  }

  domain_name               = var.domain_name
  origin_ip                 = module.compute.public_ip
  enable_autoscaling        = var.enable_autoscaling
  route_autoscaling_traffic = var.route_autoscaling_traffic
  load_balancer_dns_name    = module.compute.load_balancer_dns_name
  load_balancer_zone_id     = module.compute.load_balancer_zone_id
}

module "email" {
  source = "./modules/email"

  name           = local.name
  project_name   = var.project_name
  domain_name    = var.domain_name
  zone_id        = module.edge.zone_id
  aws_region     = var.aws_region
  aws_account_id = data.aws_caller_identity.current.account_id
}

module "database" {
  source = "./modules/database"

  name              = local.name
  project_name      = var.project_name
  subnet_group_name = module.network.database_subnet_group_name
  security_group_id = module.network.database_security_group_id
}

module "monitoring" {
  source = "./modules/monitoring"

  providers = {
    aws           = aws
    aws.us_east_1 = aws.us_east_1
  }

  name                = local.name
  project_name        = var.project_name
  environment         = var.environment
  instance_id         = module.compute.instance_id
  log_group_name      = module.observability.log_group_name
  alerts_topic_arn    = module.observability.alerts_topic_arn
  domain_name         = module.edge.domain_name
  alert_email         = var.alert_email
  nginx_5xx_threshold = var.nginx_5xx_alarm_threshold

  depends_on = [module.identity]
}
