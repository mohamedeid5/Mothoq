---
paths:
  - 'app/**'
---

# App

## Center scheduling uses local dates and UTC booking snapshots
Resolve date exceptions before weekly hours; an absent schedule is unavailable. Read duration from service_service_center and copy it to bookings; never accept client duration. Serialize all booking, schedule, timezone, and pivot-duration writes on the service-center row in a transaction. Active bookings with unknown historical duration/time provenance must not be silently reinterpreted or invalidated by schedule changes.
