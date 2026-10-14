resource "aws_acm_certificate" "load_balancer" {
  count                     = var.enable_autoscaling ? 1 : 0
  domain_name               = var.domain_name
  subject_alternative_names = ["www.${var.domain_name}", "alb.${var.domain_name}"]
  validation_method         = "DNS"
  lifecycle {
    create_before_destroy = true
  }
}

# Apex/www validation tokens are shared across ACM regions and already managed here.
resource "aws_route53_record" "load_balancer_validation" {
  count   = var.enable_autoscaling ? 1 : 0
  zone_id = data.aws_route53_zone.application.zone_id
  name    = one([for option in aws_acm_certificate.load_balancer[0].domain_validation_options : option.resource_record_name if option.domain_name == "alb.${var.domain_name}"])
  type    = "CNAME"
  ttl     = 300
  records = [one([for option in aws_acm_certificate.load_balancer[0].domain_validation_options : option.resource_record_value if option.domain_name == "alb.${var.domain_name}"])]
}

resource "aws_acm_certificate_validation" "load_balancer" {
  count                   = var.enable_autoscaling ? 1 : 0
  certificate_arn         = aws_acm_certificate.load_balancer[0].arn
  validation_record_fqdns = concat([for record in aws_route53_record.certificate_validation : record.fqdn], [aws_route53_record.load_balancer_validation[0].fqdn])
}

resource "aws_route53_record" "load_balancer" {
  count   = var.enable_autoscaling ? 1 : 0
  zone_id = data.aws_route53_zone.application.zone_id
  name    = "alb.${var.domain_name}"
  type    = "A"
  alias {
    name                   = var.load_balancer_dns_name
    zone_id                = var.load_balancer_zone_id
    evaluate_target_health = true
  }
}
