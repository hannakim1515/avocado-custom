# 003 — Preserve feedback while removing reduced-motion travel

- **Status**: DONE
- **Commit**: 26cfbf1
- **Severity**: MEDIUM
- **Category**: Accessibility
- **Estimated scope**: 2 files, CSS and existing intro branch

## Problem

`theme/basic/css/system-ui.css:394` reduces every duration to 1ms. This removes useful color/opacity feedback instead of only removing spatial movement.

## Target

Under `prefers-reduced-motion: reduce`, disable intro keyframes and transform travel, show the main composition in place, and preserve 120–150ms opacity/color feedback. The existing branch in `gpt_main.php:137` should continue skipping the boot sequence immediately.

## Repo conventions to follow

Use the existing reduced-motion query and explicit transition property lists.

## Steps

1. Replace the global 1ms override with selectors for intro animation, main reveal transforms, drawer transform, and hover transforms.
2. Keep opacity/color transitions at 120–150ms.
3. Verify the JS reduced-motion branch removes the intro and marks the client ready.

## Boundaries

- Do not remove focus feedback.
- Do not change sessionStorage behavior.

## Verification

- Toggle reduced motion in DevTools and reload: no scan, rotation, translate, or scale should run.
- Focus and color state changes should remain legible.
- Done when movement is removed without eliminating all feedback.
