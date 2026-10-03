#!/usr/bin/env bash
set -Eeuo pipefail

export AWS_REGION="${1:?Pass the AWS region}"
registry="${2:?Pass the ECR registry}"
export IMAGE_TAG="${3:?Pass the image tag}"
compose_file="${4:-/opt/mothoq/compose.yaml}"
export APP_IMAGE="$registry/mothoq/app:$IMAGE_TAG"
export NGINX_IMAGE="$registry/mothoq/nginx:$IMAGE_TAG"

cd /opt/mothoq
umask 077
exec 9>deploy.lock
flock -n 9 || { echo 'Another deployment is running.' >&2; exit 1; }

temporary_environment="$(mktemp /opt/mothoq/.env.production.XXXXXX)"
trap 'rm -f "$temporary_environment"; docker logout "$registry" >/dev/null 2>&1 || true' EXIT
aws ssm get-parameter --region "$AWS_REGION" --name /mothoq/production/env \
  --with-decryption --query Parameter.Value --output text > "$temporary_environment"
mv "$temporary_environment" .env.production

aws ecr get-login-password --region "$AWS_REGION" \
  | docker login --username AWS --password-stdin "$registry"
docker compose --project-directory /opt/mothoq --env-file .env.production -f "$compose_file" config --quiet
docker compose --project-directory /opt/mothoq --env-file .env.production -f "$compose_file" pull

if [[ "$compose_file" != /opt/mothoq/compose.yaml ]]; then
  cp compose.yaml compose.previous.yaml
  cp "$compose_file" compose.next.yaml
  mv compose.next.yaml compose.yaml
fi

docker compose --env-file .env.production run --rm --no-deps --user root --entrypoint sh app \
  -c 'chown www-data:www-data /var/www/html/storage/app/public; chmod 755 /var/www/html/storage/app/public'
docker compose --env-file .env.production run --rm app php artisan migrate --force
docker compose --env-file .env.production up -d --remove-orphans --wait --wait-timeout 180
curl --fail --silent --show-error --retry 12 --retry-delay 5 --retry-all-errors http://127.0.0.1/up >/dev/null
/usr/local/bin/mothoq-enable-https

if [[ "$0" != /usr/local/bin/mothoq-deploy ]]; then
  install -m 0750 "$0" /usr/local/bin/mothoq-deploy
fi
echo 'Deployment completed successfully.'
