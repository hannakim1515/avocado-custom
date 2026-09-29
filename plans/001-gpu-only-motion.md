# 001 — Keep the boot sequence on compositor-friendly properties

- **Status**: DONE
- **Commit**: 26cfbf1
- **Severity**: HIGH
- **Category**: Performance
- **Estimated scope**: 1 file, small CSS edit

## Problem

`theme/basic/css/system-ui.css:290` moves the scan with `top`, which triggers layout/paint, and the login button transitions `filter` at lines 218–220.

```css
@keyframes boot-scan { 0% { top: -10%; } 100% { top: 110%; } }
.re-login-btn { transition: filter 150ms ease, transform 150ms var(--client-ease-out); }
```

## Target

Animate the scan with `transform: translate3d()` and replace the button's animated filter with color/opacity feedback. Keep the 1900ms scan because it is part of a rare first-entry explanatory sequence; keep interactive button feedback at 150ms.

## Repo conventions to follow

Use `--client-ease-out: cubic-bezier(0.23, 1, 0.32, 1)` from the same stylesheet. Predetermined motion remains CSS-only.

## Steps

1. In `theme/basic/css/system-ui.css`, pin the scan at `top: 0` and move it from `translate3d(0,-10vh,0)` to `translate3d(0,110vh,0)`.
2. Remove `filter` from the login button transition and hover state; use background-color or opacity instead.

## Boundaries

- Do not change the intro duration or markup.
- Do not add dependencies.

## Verification

- Search the motion CSS and confirm no animated layout properties remain.
- In DevTools at 10% playback, confirm the scan crosses the screen smoothly.
- Done when scan and button motion use transform/opacity or color only.
