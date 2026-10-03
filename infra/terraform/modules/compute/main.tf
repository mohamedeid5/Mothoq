data "aws_ssm_parameter" "ubuntu_2404" {
  name = "/aws/service/canonical/ubuntu/server/noble/stable/current/amd64/hvm/ebs-gp3/ami-id"
}

data "aws_eip" "existing" {
  count = var.existing_eip_allocation_id == null ? 0 : 1
  id    = var.existing_eip_allocation_id
}

resource "aws_instance" "app" {
  ami                         = data.aws_ssm_parameter.ubuntu_2404.value
  instance_type               = var.instance_type
  subnet_id                   = var.subnet_id
  vpc_security_group_ids      = [var.security_group_id]
  iam_instance_profile        = var.instance_profile_name
  associate_public_ip_address = true
  monitoring                  = false
  user_data_replace_on_change = false

  user_data = templatefile("${path.module}/templates/user-data.sh.tftpl", {
    compose_version     = var.compose_version
    nginx_config_base64 = base64encode(file("${path.module}/../../../../docker/nginx/default.prod.conf"))
    tls_script_base64 = base64encode(templatefile("${path.module}/templates/enable-https.sh.tftpl", {
      domain_name = var.domain_name
    }))
    compose_file_base64  = base64encode(file("${path.module}/../../../../docker/compose.ec2.yaml"))
    deploy_script_base64 = base64encode(file("${path.module}/../../../../docker/deploy.sh"))
  })

  root_block_device {
    volume_type           = "gp3"
    volume_size           = var.root_volume_size
    encrypted             = true
    delete_on_termination = true
  }

  metadata_options {
    http_endpoint               = "enabled"
    http_tokens                 = "required"
    http_put_response_hop_limit = 2
  }

  lifecycle {
    ignore_changes = [ami, user_data]

    precondition {
      condition     = !startswith(var.instance_type, "t4g.")
      error_message = "The Ubuntu AMI uses x86_64. Choose t3 or an x86_64 Flex instance."
    }
  }

  tags = {
    Name = var.name
  }
}

resource "aws_eip_association" "existing" {
  count         = var.existing_eip_allocation_id == null ? 0 : 1
  allocation_id = var.existing_eip_allocation_id
  instance_id   = aws_instance.app.id
}
