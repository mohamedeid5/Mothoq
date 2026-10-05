locals {
  lifecycle_policy = jsonencode({
    rules = [{
      rulePriority = 1
      description  = "Keep the ten most recent images"
      selection = {
        tagStatus   = "any"
        countType   = "imageCountMoreThan"
        countNumber = 10
      }
      action = {
        type = "expire"
      }
    }]
  })
}

resource "aws_ecr_repository" "app" {
  name                 = "${var.project_name}/app"
  image_tag_mutability = "IMMUTABLE"
  force_delete         = var.force_delete_repositories

  image_scanning_configuration {
    scan_on_push = true
  }
}

resource "aws_ecr_repository" "nginx" {
  name                 = "${var.project_name}/nginx"
  image_tag_mutability = "IMMUTABLE"
  force_delete         = var.force_delete_repositories

  image_scanning_configuration {
    scan_on_push = true
  }
}

resource "aws_ecr_lifecycle_policy" "app" {
  repository = aws_ecr_repository.app.name
  policy     = local.lifecycle_policy
}

resource "aws_ecr_lifecycle_policy" "nginx" {
  repository = aws_ecr_repository.nginx.name
  policy     = local.lifecycle_policy
}

resource "aws_s3_bucket" "uploads" {
  bucket        = var.uploads_bucket_name
  force_destroy = false
}

resource "aws_s3_bucket_public_access_block" "uploads" {
  bucket = aws_s3_bucket.uploads.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_ownership_controls" "uploads" {
  bucket = aws_s3_bucket.uploads.id

  rule {
    object_ownership = "BucketOwnerEnforced"
  }
}

resource "aws_s3_bucket_versioning" "uploads" {
  bucket = aws_s3_bucket.uploads.id

  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "uploads" {
  bucket = aws_s3_bucket.uploads.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

resource "aws_s3_bucket_policy" "uploads" {
  bucket = aws_s3_bucket.uploads.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid       = "DenyInsecureTransport"
      Effect    = "Deny"
      Principal = "*"
      Action    = "s3:*"
      Resource  = [aws_s3_bucket.uploads.arn, "${aws_s3_bucket.uploads.arn}/*"]
      Condition = {
        Bool = { "aws:SecureTransport" = "false" }
      }
    }]
  })

  depends_on = [aws_s3_bucket_public_access_block.uploads]
}

resource "aws_iam_role_policy" "uploads" {
  name = "${aws_s3_bucket.uploads.id}-access"
  role = var.instance_role_name

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid      = "ReadUploadsBucketLocation"
        Effect   = "Allow"
        Action   = "s3:GetBucketLocation"
        Resource = aws_s3_bucket.uploads.arn
      },
      {
        Sid      = "ListServiceCenterImages"
        Effect   = "Allow"
        Action   = "s3:ListBucket"
        Resource = aws_s3_bucket.uploads.arn
        Condition = {
          StringLike = { "s3:prefix" = ["service-centers/", "service-centers/*"] }
        }
      },
      {
        Sid    = "ManageServiceCenterImages"
        Effect = "Allow"
        Action = [
          "s3:GetObject",
          "s3:PutObject",
          "s3:DeleteObject",
          "s3:AbortMultipartUpload",
          "s3:ListMultipartUploadParts",
        ]
        Resource = "${aws_s3_bucket.uploads.arn}/service-centers/*"
      }
    ]
  })
}
