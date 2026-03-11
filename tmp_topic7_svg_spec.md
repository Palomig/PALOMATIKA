# Spec: Topic 07 SVG fidelity restoration (PNG -> SVG parity)

## Context
Production screenshot shows recurring mismatch in Topic 07 number-line tasks:
- SVG point `a` is often centered between integers (e.g. 6.5)
- Source PNG indicates point should be offset (e.g. between 7 and 8, closer to 7)
- This is reported as a mass issue across many images

## Goal
Restore high-fidelity parity between Topic 07 source PNG visuals and rendered SVG/JSON data, at scale.

## Non-goals
- No direct FTP hotfixes to production.
- No bypass of GitHub-based deployment flow.

## Constraints
- Keep versioned changes in GitHub (main branch flow as agreed).
- Preserve existing task IDs where possible.
- Maintain answer correctness consistency after geometry fixes.

## Required deliverables
1. Root-cause analysis (why point drift occurs across dataset)
2. Deterministic correction strategy for all affected tasks
3. Validation pipeline (automated checks + manual sampling)
4. Rollout plan with low regression risk

## Candidate root causes to evaluate
- Incorrect `point_value` in JSON vs source PNG
- Renderer normalization that snaps/centers positions visually
- Wrong tick range selection causing visual distortion
- Legacy baked SVG stale vs current JSON

## Needed plan structure
- Phase A: Inventory/mapping (task -> png reference -> current point)
- Phase B: Correction algorithm (data-first vs render-first)
- Phase C: Auto-validation (point-side checks, answer consistency checks)
- Phase D: Incremental rollout and QA gates

## Output format
Please provide:
- Concrete step-by-step plan
- Risk list + mitigations
- Tooling/scripts proposed
- Estimation (quick win vs full fix)
- Recommended owner split (Codex vs Claude)
