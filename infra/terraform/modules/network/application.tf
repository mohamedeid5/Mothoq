locals {
  application_zones = var.enable_private_application_network ? {
    primary = {
      availability_zone = aws_subnet.public.availability_zone
      public_subnet_id  = aws_subnet.public.id
      cidr_block        = "10.20.21.0/24"
    }
    secondary = {
      availability_zone = aws_subnet.public_secondary.availability_zone
      public_subnet_id  = aws_subnet.public_secondary.id
      cidr_block        = "10.20.22.0/24"
    }
  } : {}
}

resource "aws_subnet" "application" {
  for_each                = local.application_zones
  vpc_id                  = aws_vpc.main.id
  cidr_block              = each.value.cidr_block
  availability_zone       = each.value.availability_zone
  map_public_ip_on_launch = false

  tags = {
    Name = "${var.name}-application-${each.key}"
  }
}

resource "aws_eip" "nat" {
  for_each = local.application_zones
  domain   = "vpc"

  tags = {
    Name = "${var.name}-nat-${each.key}"
  }
}

resource "aws_nat_gateway" "application" {
  for_each          = local.application_zones
  allocation_id     = aws_eip.nat[each.key].id
  subnet_id         = each.value.public_subnet_id
  connectivity_type = "public"
  availability_mode = "zonal"

  tags = {
    Name = "${var.name}-nat-${each.key}"
  }

  depends_on = [
    aws_internet_gateway.main,
    aws_route_table_association.public,
    aws_route_table_association.public_secondary,
  ]
}

resource "aws_route_table" "application" {
  for_each = local.application_zones
  vpc_id   = aws_vpc.main.id

  tags = {
    Name = "${var.name}-application-${each.key}"
  }
}

resource "aws_route" "application_internet" {
  for_each               = local.application_zones
  route_table_id         = aws_route_table.application[each.key].id
  destination_cidr_block = "0.0.0.0/0"
  nat_gateway_id         = aws_nat_gateway.application[each.key].id
}

resource "aws_route_table_association" "application" {
  for_each       = local.application_zones
  subnet_id      = aws_subnet.application[each.key].id
  route_table_id = aws_route_table.application[each.key].id
}

resource "aws_vpc_endpoint" "s3" {
  count             = var.enable_private_application_network ? 1 : 0
  vpc_id            = aws_vpc.main.id
  service_name      = "com.amazonaws.${var.aws_region}.s3"
  vpc_endpoint_type = "Gateway"
  route_table_ids   = [for route_table in aws_route_table.application : route_table.id]

  tags = {
    Name = "${var.name}-s3"
  }
}
