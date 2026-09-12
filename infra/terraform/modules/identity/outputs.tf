output "instance_profile_name" {
  description = "Name of the IAM instance profile used by the application host."
  value       = aws_iam_instance_profile.main.name
}

output "terraform_operator_attachment_id" {
  description = "ID of the Terraform operator policy attachment."
  value       = aws_iam_user_policy_attachment.terraform_operator.id
}

output "github_actions_role_arn" {
  description = "IAM role assumed by GitHub Actions through OIDC."
  value       = aws_iam_role.github_actions.arn
}
