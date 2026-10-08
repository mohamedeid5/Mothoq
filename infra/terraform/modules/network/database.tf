resource "aws_db_subnet_group" "database" {
  name        = "${var.name}-database"
  description = "Private subnets for the Mothoq database"
  subnet_ids  = [aws_subnet.private_primary.id, aws_subnet.private_secondary.id]

  tags = {
    Name = "${var.name}-database"
  }
}

resource "aws_security_group" "database" {
  name        = "${var.name}-database"
  description = "MySQL access from the Mothoq application only"
  vpc_id      = aws_vpc.main.id

  ingress {
    description     = "MySQL from the application"
    from_port       = 3306
    to_port         = 3306
    protocol        = "tcp"
    security_groups = [aws_security_group.web.id]
  }

  egress = []

  tags = {
    Name = "${var.name}-database"
  }
}
