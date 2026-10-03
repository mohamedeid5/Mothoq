#!/usr/bin/env bash
set -Eeuo pipefail

repository_directory="$(cd "$(dirname "$0")/../.." && pwd)"
render_directory="$(mktemp -d)"
trap 'rm -rf "$render_directory"' EXIT

export TF_VAR_image_tag=1111111111111111111111111111111111111111
export TF_VAR_aws_account_id=123456789012
export TF_VAR_aws_region=eu-north-1
export IMAGE_TAG="$TF_VAR_image_tag"
export DB_DATABASE=mothoq DB_USERNAME=mothoq DB_PASSWORD=test-only DB_ROOT_PASSWORD=test-only

terraform -chdir="$repository_directory/infra/render-deployment" init -backend=false -input=false
terraform -chdir="$repository_directory/infra/render-deployment" validate
printf '%s\n' 'jsonencode(local.files)' \
  | terraform -chdir="$repository_directory/infra/render-deployment" console \
  | jq -er . > "$render_directory/files.json"

for name in compose.yaml deploy.sh enable-https.sh; do
  jq -er --arg name "$name" '.[$name]' "$render_directory/files.json" > "$render_directory/$name"
done

bash -n "$render_directory/deploy.sh"
bash -n "$render_directory/enable-https.sh"
touch "$render_directory/.env.production"
docker compose --project-directory "$render_directory" --env-file "$render_directory/.env.production" \
  -f "$render_directory/compose.yaml" config --format json > "$render_directory/compose.json"

jq -e '
  . as $config |
  .name == "mothoq" and
  .volumes.mysql_data.name == "mothoq_mysql_data" and
  .volumes.redis_data.name == "mothoq_redis_data" and
  .volumes.storage_data.name == "mothoq_storage_data" and
  (["app", "horizon", "scheduler", "nginx"] | all(.[];
    . as $service | $config.services[$service].volumes | any(.[];
      .source == "storage_data" and .target == "/var/www/html/storage/app/public" and
      ((.read_only // false) == ($service == "nginx"))))) and
  (.services.app.image | endswith(":" + env.IMAGE_TAG))
' "$render_directory/compose.json" >/dev/null

printf '%s\n' 'jsonencode(local.ssm_parameters)' \
  | terraform -chdir="$repository_directory/infra/render-deployment" console \
  | jq -er . > "$render_directory/ssm.json"
jq -er '.commands | join("\n")' "$render_directory/ssm.json" | bash -n

export UPLOAD_SOURCE="$render_directory/source"
export UPLOAD_TARGET="$render_directory/target"
export UPLOAD_OWNER="$(id -u):$(id -g)"
mkdir -p "$UPLOAD_SOURCE/nested" "$UPLOAD_TARGET"
printf 'original image\n' > "$UPLOAD_SOURCE/nested/image with spaces.png"
sh "$repository_directory/docker/migrate-uploads.sh"
cmp "$UPLOAD_SOURCE/nested/image with spaces.png" "$UPLOAD_TARGET/nested/image with spaces.png"
sh "$repository_directory/docker/migrate-uploads.sh"

printf 'conflicting image\n' > "$UPLOAD_TARGET/nested/image with spaces.png"
if sh "$repository_directory/docker/migrate-uploads.sh"; then
  echo 'Migration incorrectly accepted a conflicting upload.' >&2
  exit 1
fi
grep -qx 'conflicting image' "$UPLOAD_TARGET/nested/image with spaces.png"
grep -qx 'original image' "$UPLOAD_SOURCE/nested/image with spaces.png"

ln -s "$UPLOAD_SOURCE/nested" "$UPLOAD_SOURCE/link"
if sh "$repository_directory/docker/migrate-uploads.sh"; then
  echo 'Migration incorrectly accepted a symlink.' >&2
  exit 1
fi

echo 'Deployment templates, shell syntax, image tag and persistent volume contracts passed.'
echo 'Upload copy, repeat-copy, conflict preservation and symlink rejection passed.'
