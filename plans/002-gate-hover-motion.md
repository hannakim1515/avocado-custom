# 002 — Gate hover movement to precise pointers

- **Status**: DONE
- **Commit**: 26cfbf1
- **Severity**: MEDIUM
- **Category**: Accessibility
- **Estimated scope**: 1 file, small CSS edit

## Problem

`theme/basic/css/system-ui.css:191` and `:219` apply translated hover feedback to all pointer types. Touch browsers can retain false hover states.

## Target

Wrap transform-based hover rules in `@media (hover: hover) and (pointer: fine)`. Keep focus-visible feedback outside the media query and at 150ms.

## Repo conventions to follow

Use the existing explicit property transitions and `--client-ease-out`. Do not introduce `transition: all`.

## Steps

1. Make base `:focus-visible` rules provide border/color feedback without depending on hover support.
2. Move translated hover feedback into the precise-pointer media query.
3. Remove the current `@media (hover: none)` transform reset.

## Boundaries

- Do not alter layout or link destinations.
- Do not add hover scale effects.

## Verification

- Emulate touch: taps must not leave buttons translated.
- Keyboard focus must remain clearly visible.
- Done when all hover-only transform motion is pointer-gated.
