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
  environment_parameter_arn = local.environment_parameter_arn
  github_oidc_subject       = local.github_oidc_subject
}

module "network" {
  source = "./modules/network"

  name               = local.name
  allowed_http_cidrs = var.allowed_http_cidrs
}

module "storage" {
  source = "./modules/storage"

  project_name              = var.project_name
  force_delete_repositories = var.force_delete_repositories

  depends_on = [module.identity]
}

module "observability" {
  source = "./modules/observability"

  project_name = var.project_name
  environment  = var.environment

  depends_on = [module.identity]
}

module "compute" {
  source = "./modules/compute"

  name                       = local.name
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
