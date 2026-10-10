---
paths:
  - 'database/**'
---

# Database

## Preserve historical scheduling data during upgrades
Existing durations and scheduled_at_timezone stay NULL. Never bulk-fill durations or relabel old scheduled_at values as UTC; scheduling:audit is read-only. New bookings explicitly record UTC provenance. Egyptian centers use Africa/Cairo for wall-clock schedules; historical booking interpretation requires verified human input.

## Local-time migration replaces timezone provenance
This supersedes the earlier timezone-provenance rule. Keep shared migrations immutable; the new migration drops timezone columns without modifying booking times, durations or statuses. It refuses existing UTC-tagged bookings or non-Egyptian centers until reviewed explicitly. Legacy durations stay NULL; scheduling:audit remains read-only. Rollback restores nullable provenance, never labels local values UTC.
