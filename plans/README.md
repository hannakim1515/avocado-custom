# Animation plans

| # | Plan | Severity | Status |
| --- | --- | --- | --- |
| 001 | Keep the boot sequence on compositor-friendly properties | HIGH | DONE |
| 002 | Gate hover movement to precise pointers | MEDIUM | DONE |
| 003 | Preserve feedback while removing reduced-motion travel | MEDIUM | DONE |

Recommended order: 001 → 002 → 003. The plans are independent, but completing them in this order removes the performance issue before accessibility polish.
