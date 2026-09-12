locals {
  name                      = "${var.project_name}-${var.environment}"
  parameter_path            = "/${var.project_name}/${var.environment}/env"
  compose_version           = "v5.1.4"
  app_repository_arn        = "arn:aws:ecr:${var.aws_region}:${data.aws_caller_identity.current.account_id}:repository/${var.project_name}/app"
  nginx_repository_arn      = "arn:aws:ecr:${var.aws_region}:${data.aws_caller_identity.current.account_id}:repository/${var.project_name}/nginx"
  log_group_arn             = "arn:aws:logs:${var.aws_region}:${data.aws_caller_identity.current.account_id}:log-group:/${var.project_name}/${var.environment}"
  environment_parameter_arn = "arn:aws:ssm:${var.aws_region}:${data.aws_caller_identity.current.account_id}:parameter${local.parameter_path}"
  github_oidc_subject       = "repo:${var.github_repository_owner}@${var.github_repository_owner_id}/${var.github_repository_name}@${var.github_repository_id}:ref:refs/heads/${var.github_deployment_branch}"
}
