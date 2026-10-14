#!/usr/bin/env bash
set -Eeuo pipefail

export AWS_REGION="${1:?Pass the AWS region}"
group_name="${2:?Pass the Auto Scaling group name}"
release="${3:?Pass the 40-character Git SHA}"
[[ "$release" =~ ^[0-9a-f]{40}$ ]] || { echo 'Invalid release tag' >&2; exit 1; }

work_directory="$(mktemp -d)"
trap 'rm -rf "$work_directory"' EXIT
aws autoscaling describe-auto-scaling-groups --auto-scaling-group-names "$group_name" > "$work_directory/group.json"
launch_template_id="$(jq -er '.AutoScalingGroups[0].LaunchTemplate.LaunchTemplateId' "$work_directory/group.json")"
current_version="$(jq -er '.AutoScalingGroups[0].LaunchTemplate.Version' "$work_directory/group.json")"
[[ "$current_version" =~ ^[0-9]+$ ]] || { echo 'Rollback requires a numeric launch template version' >&2; exit 1; }
instance_id="$(jq -er '[.AutoScalingGroups[0].Instances[] | select(.HealthStatus == "Healthy" and .LifecycleState == "InService")][0].InstanceId' "$work_directory/group.json")"

# Never overlap an infrastructure refresh, rollback or another deployment.
active_refreshes="$(aws autoscaling describe-instance-refreshes --auto-scaling-group-name "$group_name" \
  --query "InstanceRefreshes[?Status=='Pending' || Status=='InProgress' || Status=='Baking' || Status=='Cancelling' || Status=='RollbackInProgress'].InstanceRefreshId" --output text)"
[[ -z "$active_refreshes" ]] || { echo 'An instance refresh is already active' >&2; exit 1; }

# The existing host runs the new image only as a migration job. Its serving containers stay unchanged.
# Migrations must remain backward compatible with the previous release during refresh/rollback.
migration_script="$(cat <<'SCRIPT'
set -Eeuo pipefail
cd /opt/mothoq
set -a
. ./.env
set +a
APP_IMAGE="${APP_IMAGE%:*}:$1"
export APP_IMAGE
registry="${APP_IMAGE%%/*}"
trap 'docker logout "$registry" >/dev/null 2>&1 || true' EXIT
aws ecr get-login-password --region "$AWS_REGION" | docker login --username AWS --password-stdin "$registry"
docker compose pull app
docker compose run --rm --no-deps app php artisan migrate --force --isolated=1 --no-interaction
docker compose run --rm --no-deps app php artisan app:check-scaling-readiness --no-interaction
SCRIPT
)"
jq -n --arg script "$migration_script" --arg release "$release" '{
  executionTimeout: ["900"],
  commands: [("bash -c " + ($script | @sh) + " -- " + ($release | @sh))]
}' > "$work_directory/migrate.json"
command_id="$(aws ssm send-command --instance-ids "$instance_id" --document-name AWS-RunShellScript \
  --timeout-seconds 900 --parameters "file://$work_directory/migrate.json" --query Command.CommandId --output text)"
status=Pending
for attempt in {1..100}; do
  status="$(aws ssm get-command-invocation --command-id "$command_id" --instance-id "$instance_id" --query Status --output text 2>/dev/null || true)"
  case "$status" in
    Success) break ;;
    Failed|TimedOut|Cancelled|Cancelling) echo "Migration failed: $status (SSM command $command_id)" >&2; exit 1 ;;
  esac
  sleep 10
done
[[ "$status" == Success ]] || { echo "Migration timed out (SSM command $command_id)" >&2; exit 1; }

# Include the latest Terraform infrastructure changes while pinning this release on each new instance.
aws ec2 describe-launch-template-versions --launch-template-id "$launch_template_id" --versions '$Latest' > "$work_directory/template.json"
source_version="$(jq -er '.LaunchTemplateVersions[0].VersionNumber' "$work_directory/template.json")"
jq --arg release "$release" '{TagSpecifications: [.LaunchTemplateVersions[0].LaunchTemplateData.TagSpecifications[] |
  if .ResourceType == "instance" then .Tags = ([.Tags[] | select(.Key != "Release")] + [{Key: "Release", Value: $release}]) else . end
]}' "$work_directory/template.json" > "$work_directory/release.json"
version="$(aws ec2 create-launch-template-version --launch-template-id "$launch_template_id" \
  --source-version "$source_version" --version-description "$release" \
  --launch-template-data "file://$work_directory/release.json" --query LaunchTemplateVersion.VersionNumber --output text)"
jq -n --arg id "$launch_template_id" --arg version "$version" '{LaunchTemplate: {LaunchTemplateId: $id, Version: $version}}' > "$work_directory/desired.json"
refresh_id="$(aws autoscaling start-instance-refresh --auto-scaling-group-name "$group_name" \
  --desired-configuration "file://$work_directory/desired.json" \
  --preferences '{"MinHealthyPercentage":100,"MaxHealthyPercentage":150,"InstanceWarmup":300,"AutoRollback":true,"SkipMatching":true}' \
  --query InstanceRefreshId --output text)"
echo "Started instance refresh $refresh_id for release $release"
for attempt in {1..180}; do
  status="$(aws autoscaling describe-instance-refreshes --auto-scaling-group-name "$group_name" \
    --instance-refresh-ids "$refresh_id" --query 'InstanceRefreshes[0].Status' --output text)"
  case "$status" in
    Successful) echo 'Fleet deployment completed successfully.'; exit 0 ;;
    Failed|Cancelled|RollbackFailed|RollbackSuccessful)
      echo "Deployment did not complete: $status (refresh $refresh_id)" >&2; exit 1 ;;
  esac
  sleep 30
done
echo "Refresh $refresh_id has not completed; inspect its status in AWS before retrying." >&2
exit 1
