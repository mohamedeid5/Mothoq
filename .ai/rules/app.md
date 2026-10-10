---
paths:
  - 'app/**'
---

# App

## Center scheduling uses local dates and UTC booking snapshots
Resolve date exceptions before weekly hours; an absent schedule is unavailable. Read duration from service_service_center and copy it to bookings; never accept client duration. Serialize all booking, schedule, timezone, and pivot-duration writes on the service-center row in a transaction. Active bookings with unknown historical duration/time provenance must not be silently reinterpreted or invalidated by schedule changes.

## Egypt local booking times replace the earlier UTC design
User decision on 2026-10-10 supersedes the earlier UTC/timezone rule: booking input, storage, filters and display use Egypt local time with app.timezone Africa/Cairo. No per-center timezone or booking timezone provenance in runtime code; reject offset-bearing booking inputs. Preserve duration snapshots, exception precedence and center-row locking. Active bookings without a duration require review before conflicting schedule edits.
