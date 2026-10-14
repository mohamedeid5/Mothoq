mock_provider "aws" {
  mock_data "aws_availability_zones" {
    defaults = {
      names = ["eu-north-1a", "eu-north-1b"]
    }
  }
}

run "private_application_network" {
  command = apply
  module {
    source = "./modules/network"
  }
  variables {
    name                               = "mothoq-test"
    aws_region                         = "eu-north-1"
    allowed_http_cidrs                 = ["127.0.0.1/32"]
    enable_private_application_network = true
  }

  assert {
    condition     = length(aws_subnet.application) == 2 && length(aws_nat_gateway.application) == 2
    error_message = "Each application AZ needs its own subnet and NAT."
  }
  assert {
    condition = alltrue([for key, subnet in aws_subnet.application :
      !subnet.map_public_ip_on_launch &&
      subnet.availability_zone == local.application_zones[key].availability_zone &&
      aws_nat_gateway.application[key].subnet_id == local.application_zones[key].public_subnet_id &&
      aws_route.application_internet[key].nat_gateway_id == aws_nat_gateway.application[key].id &&
      aws_route.application_internet[key].route_table_id == aws_route_table.application[key].id &&
      aws_route_table_association.application[key].subnet_id == subnet.id &&
      aws_route_table_association.application[key].route_table_id == aws_route_table.application[key].id
    ])
    error_message = "Private instances must use the NAT and route table belonging to their own AZ."
  }
  assert {
    condition = (length(aws_route_table.private.route) == 0 &&
      aws_route_table_association.private_primary.route_table_id == aws_route_table.private.id &&
    aws_route_table_association.private_secondary.route_table_id == aws_route_table.private.id)
    error_message = "Database subnets must remain isolated without a default internet route."
  }
  assert {
    condition = (aws_vpc_endpoint.s3[0].vpc_endpoint_type == "Gateway" &&
      aws_vpc_endpoint.s3[0].service_name == "com.amazonaws.eu-north-1.s3" &&
    toset(aws_vpc_endpoint.s3[0].route_table_ids) == toset([for table in aws_route_table.application : table.id]))
    error_message = "S3 must use the gateway endpoint from both application route tables."
  }
  assert {
    condition     = length(setintersection(toset(output.application_subnet_ids), toset(output.private_subnet_ids))) == 0
    error_message = "Application and data subnets must be separate."
  }
}

run "disabled_fleet_has_no_nat_cost" {
  command = apply
  module {
    source = "./modules/network"
  }
  variables {
    name                               = "mothoq-test"
    aws_region                         = "eu-north-1"
    allowed_http_cidrs                 = ["127.0.0.1/32"]
    enable_private_application_network = false
  }
  assert {
    condition     = length(aws_subnet.application) == 0 && length(aws_nat_gateway.application) == 0 && length(aws_eip.nat) == 0 && length(aws_vpc_endpoint.s3) == 0
    error_message = "The disabled fleet must not provision NAT gateways, Elastic IPs or application endpoints."
  }
}
