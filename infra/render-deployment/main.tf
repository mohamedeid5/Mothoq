terraform {
  required_version = ">= 1.10.0, < 2.0.0"
}

variable "image_tag" {
  type = string

  validation {
    condition     = can(regex("^[0-9a-f]{40}$", var.image_tag))
    error_message = "image_tag must be a full Git commit SHA."
  }
}

variable "aws_account_id" {
  type = string

  validation {
    condition     = can(regex("^[0-9]{12}$", var.aws_account_id))
    error_message = "aws_account_id must contain 12 digits."
  }
}

variable "aws_region" {
  type    = string
  default = "eu-north-1"
}

variable "domain_name" {
  type    = string
  default = "mothoq.store"
}

locals {
  templates     = "${path.module}/../terraform/modules/compute/templates"
  registry_host = "${var.aws_account_id}.dkr.ecr.${var.aws_region}.amazonaws.com"
  release_path  = "/opt/mothoq/releases/${var.image_tag}"

  files = {
    "compose.yaml" = templatefile("${local.templates}/compose.ec2.yaml.tftpl", {
      app_repository   = "${local.registry_host}/mothoq/app"
      nginx_repository = "${local.registry_host}/mothoq/nginx"
      aws_region       = var.aws_region
      log_group_name   = "/mothoq/production"
    })
    "deploy.sh" = templatefile("${local.templates}/deploy.sh.tftpl", {
      aws_region          = var.aws_region
      parameter_name      = "/mothoq/production/env"
      registry_host       = local.registry_host
      nginx_config_base64 = base64encode(file("${path.module}/../../docker/nginx/default.prod.conf"))
    })
    "enable-https.sh" = templatefile("${local.templates}/enable-https.sh.tftpl", {
      domain_name = var.domain_name
    })
  }

  ssm_parameters = {
    executionTimeout = ["900"]
    commands = concat(
      ["set -eu", "umask 077", "install -d -m 0750 '${local.release_path}'"],
      [for name, content in local.files : "printf '%s' '${base64encode(content)}' | base64 --decode > '${local.release_path}/${name}'"],
      ["IMAGE_TAG='${var.image_tag}' bash '${local.release_path}/deploy.sh' '${local.release_path}'"]
    )
  }
}
