---
paths:
  - 'database/**'
---

# Database

## Preserve historical scheduling data during upgrades
Existing durations and scheduled_at_timezone stay NULL. Never bulk-fill durations or relabel old scheduled_at values as UTC; scheduling:audit is read-only. New bookings explicitly record UTC provenance. Egyptian centers use Africa/Cairo for wall-clock schedules; historical booking interpretation requires verified human input.
