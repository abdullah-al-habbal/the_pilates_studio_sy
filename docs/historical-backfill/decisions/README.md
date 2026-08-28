# Decision log

One file per codified ruling. A decision links to the finding that motivated it; it does not
restate the evidence.

| ID | Decision | Motivating finding | Date | Status |
|---|---|---|---|---|
| [D-A01](D-A01-active-booking-conflict.md) | Active-booking conflict — hard rejection with clear message | [A01](../findings/A01-active-booking-constraint.md) | 2026-08-27 | **Accepted** |
| [D-A02](D-A02-null-validity-packages.md) | Null-validity packages — hard rejection | [A02](../findings/A02-null-validity-packages.md) | 2026-08-27 | **Accepted** |
| [D-A03](D-A03-exchange-rate-override.md) | Exchange rate — optional admin override, current-rate default | PRD §8.1 / §10 | 2026-08-27 | **Accepted** |
| [D-A05](D-A05-purchased-at-column.md) | Add the `purchased_at` column | [A05](../findings/A05-purchased-at-column.md) | 2026-08-27 | **Accepted** |
| [D-A13](D-A13-global-purchased-at-adoption.md) | `purchased_at` becomes the project-wide business date | [A13](../findings/A13-revenue-contamination.md) | 2026-08-27 | **Accepted** |
| [D-A15](D-A15-booking-sessions-business-date.md) | `class_sessions.date` is the business date for `booking_sessions` | [A15](../findings/A15-booking-session-business-date.md) | 2026-08-28 | **Accepted** |

## Pending

None. All decisions are closed.

## Template

```markdown
# D-Axx — <title>

| | |
|---|---|
| **Motivating finding** | [Axx](../findings/Axx-....md) |
| **Date** | YYYY-MM-DD |
| **Status** | Accepted / Superseded |

## Ruling
## Rationale
## Allowed outcomes
## Reference implementation
## Plan impact
```
