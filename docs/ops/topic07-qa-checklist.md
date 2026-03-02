# Topic 07 — QA Checklist for Perfect-Fidelity Rollout

**Topic:** 07 — Числа, координатная прямая (Numbers, coordinate line)
**Date created:** 2026-03-02
**Scope:** 127 tasks across 35 zadaniya in 3 blocks, 22 distinct task types

---

## 1. PNG-to-JSON Parity Review

Topic 07 has **74 PDF reference images** in `docs/oge_data/images/oge07_*.png` and
**76 legacy PNGs** in `public/images/tasks/07/` (plus duplicates in `public/images/tasks/x07tmp/`).

### 1.1 Visual spot-check matrix

For each block, open the reference PNG side-by-side with the rendered page
(`/test/7` or `/topics/07`) and compare:

| Check | What to verify | Tool |
|-------|---------------|------|
| **Number-line scale** | Tick marks, arrow heads, label positions match PDF | Browser overlay |
| **Point placement** | Marked points (A, B, C, ...) sit at the correct position on the line | Manual calculation |
| **Interval shading** | Open/closed endpoints rendered correctly (hollow vs filled circle) | Visual |
| **Fraction labels** | KaTeX-rendered fractions match PDF originals (numerator/denominator) | Visual |
| **sqrt labels** | `\sqrt{n}` values render correctly, no raw LaTeX leaking | Visual |

### 1.2 Coverage audit

| Item | Count | Notes |
|------|-------|-------|
| PDF reference images | 74 | `docs/oge_data/images/oge07_p*` |
| Legacy PNGs (07/) | 76 | `public/images/tasks/07/img-000..075.png` |
| Staging PNGs (x07tmp/) | 76 | `public/images/tasks/x07tmp/` — same set, should be cleaned up post-rollout |
| JSON tasks | 127 | `storage/app/tasks/topic_07.json` |
| Tasks with baked SVG | 54 (42.5%) | See breakdown in section 3 |
| Tasks without SVG | 73 (57.5%) | These rely on KaTeX-only rendering (no coordinate diagram) |

### 1.3 Checklist

- [ ] Verify every zadanie with `simple_choice` type (10 zadaniya, 0 tasks each) renders correctly even with zero tasks — no empty-container artifacts
- [ ] Spot-check at least 3 tasks per SVG-bearing type against their source PDF page
- [ ] Confirm `x07tmp/` directory can be removed after rollout (it duplicates `07/`)

---

## 2. Answer Consistency Checks

All 127 tasks have an `answer` field (none missing).

### 2.1 Answer format summary

| Type | Answers | Format | Concern |
|------|---------|--------|---------|
| `choice`, `fraction_choice`, etc. (option-index types) | 115 tasks answer `"1"` | 1-based option index | Plausible — most correct answers placed as option 1 |
| `choice` B1/Z1 | 5 tasks with answers 2, 3 | 1-based option index | Verified: non-trivial option indices |
| `count_integers` (T1-T4) | 4, 6, 7, 7 | Integer count | Correct for `\sqrt{n}` ranges |
| `count_integers` (T5-T8) | 162286, 4782625, 923, 4194182 | Integer count | **SUSPECT** — power ranges `6^7..7^6` etc. yield large counts; verify math |

### 2.2 Checklist

- [ ] **CRITICAL — Verify count_integers T5-T8 answers manually:**
  - T5: integers between `6^7` (279936) and `7^6` (117649) → expected count should be ~162286? But `6^7 > 7^6`, so the interval may be reversed — **check sign**
  - T6: `3^{14}` (4782969) vs `7^3` (343) → count = 4782625? Verify `|3^{14} - 7^3| - 1`
  - T7: `2^{10}` (1024) vs `10^2` (100) → count = 923? Verify `1024 - 100 - 1 = 923`
  - T8: `4^{11}` (4194304) vs `11^2` (121) → count = 4194182? Verify `4194304 - 121 - 1 = 4194182`
- [ ] Confirm that the 115 tasks with answer `"1"` genuinely have the correct answer as option 1 (sample at least 10 randomly)
- [ ] Run `TaskAnswerResolver` scoring logic against all 127 tasks with their declared answers — all should return `correct`
- [ ] Verify answer types are consistent: `int` vs `str` (currently mixed: 5 tasks use `int`, 122 use `str`)

---

## 3. SVG Bake Persistence Verification

Topic 07 uses `php artisan svg:bake 07` which reads from `topic_07.json` itself
(not a separate `_geometry.json`) and writes SVG strings back into the same file.

### 3.1 SVG coverage by task type

| Task type | SVG | Total | Coverage | Notes |
|-----------|-----|-------|----------|-------|
| choice | 12 | 12 | 100% | Number-line diagrams |
| comparison | 2 | 2 | 100% | |
| fraction_choice | 6 | 6 | 100% | |
| fraction_options | 12 | 12 | 100% | |
| fraction_point | 4 | 4 | 100% | |
| ordering | 4 | 4 | 100% | |
| point_value | 4 | 4 | 100% | |
| power_choice | 4 | 4 | 100% | |
| sqrt_options | 6 | 6 | 100% | |
| between_fractions | 0 | 6 | 0% | No diagram needed (text-only) |
| compare_fractions | 0 | 2 | 0% | Text-only |
| count_integers | 0 | 8 | 0% | Text-only |
| decimal_choice | 0 | 6 | 0% | Text-only |
| false_statements | 0 | 2 | 0% | Text-only |
| interval_choice | 0 | 12 | 0% | **Candidate for SVG — has number-line context** |
| negative_interval | 0 | 4 | 0% | **Candidate for SVG** |
| negative_segment | 0 | 4 | 0% | **Candidate for SVG** |
| segment_choice | 0 | 6 | 0% | **Candidate for SVG** |
| simple_choice | 0 | 0 | N/A | Zero tasks (metadata-only zadaniya) |
| sqrt_choice | 0 | 6 | 0% | Text-only |
| sqrt_interval | 0 | 12 | 0% | **Candidate for SVG** |
| sqrt_segment | 0 | 5 | 0% | **Candidate for SVG** |

### 3.2 Bake idempotency test

- [ ] Run `php artisan svg:bake 07` twice in succession
- [ ] Diff the output: `diff <(cat topic_07.json) <(php artisan svg:bake 07 && cat topic_07.json)` — must be identical (no SVG drift)
- [ ] Verify all 54 SVGs have valid `viewBox` attributes
- [ ] Verify no SVG contains raw `<script>` tags or event handlers

### 3.3 Server persistence

- [ ] After `git pull` on production, verify `topic_07.json` file size matches local (~206 KB)
- [ ] Run `php artisan deploy:refresh` on server — confirm it does NOT re-bake topic 07 (no `_geometry.json` to trigger auto-bake)
- [ ] Verify `php artisan cache:clear` does not destroy baked SVGs (they live in JSON, not cache)
- [ ] Confirm SVGs render on production at `https://cw95865.tmweb.ru/test/7`

### 3.4 Edge cases

- [ ] Verify topic 07 works in OGE variant generator (`/oge/{hash}`) — task 7 card renders SVG when present, falls back to text when absent
- [ ] Confirm `OgeVariantBuilderService` picks tasks from topic 07 correctly with deterministic seeding
- [ ] Test that `simple_choice` zadaniya (0 tasks) do not cause null errors in the variant builder

---

## 4. Release Acceptance Criteria

### 4.1 Gate: Must pass before merge to main

| # | Criterion | Verified |
|---|-----------|----------|
| 1 | All 127 tasks load without JS errors on `/test/7` | [ ] |
| 2 | All 54 SVGs render as visible coordinate-line diagrams (not blank rectangles) | [ ] |
| 3 | KaTeX renders all `$...$` expressions without fallback to raw LaTeX | [ ] |
| 4 | No task shows a broken image icon (all legacy PNG refs removed or working) | [ ] |
| 5 | `count_integers` T5-T8 answers verified mathematically correct | [ ] |
| 6 | `php artisan svg:bake 07` is idempotent (no diff on re-run) | [ ] |
| 7 | OGE variant with topic 07 task renders correctly at `/oge/{any-hash}` | [ ] |
| 8 | Mobile viewport (375px width) — SVGs scale properly, no horizontal scroll | [ ] |
| 9 | All 127 tasks have `status: "draft"` — confirm intent before promoting to `production` | [ ] |
| 10 | `x07tmp/` directory flagged for cleanup (not blocking, but tracked) | [ ] |

### 4.2 Gate: Must pass on production after deploy

| # | Criterion | Verified |
|---|-----------|----------|
| 1 | `https://cw95865.tmweb.ru/test/7` loads all 3 blocks | [ ] |
| 2 | `deploy:refresh` completes without error | [ ] |
| 3 | Generate 3 OGE variants — all include a valid task 7 | [ ] |
| 4 | No 500 errors in Laravel log (`storage/logs/laravel.log`) for topic 07 routes | [ ] |
| 5 | Page load time < 3s on 3G throttle (Chrome DevTools) | [ ] |

### 4.3 Known risks and mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| `count_integers` answers T5-T8 may be wrong | Students get incorrect scoring | Manual math verification before promoting to `production` status |
| 73 tasks lack SVG (57.5%) | Inconsistent visual experience | Acceptable for text-only types; track interval/segment types as future SVG candidates |
| All tasks are `draft` status | Not served in production variant pool | Intentional — promote after QA pass |
| `x07tmp/` duplicates `07/` images | Wasted disk space | Delete after confirming no references point to it |
| Mixed answer types (int vs str) | Potential scoring mismatch in `TaskAnswerResolver` | Normalize all to string before comparison |

---

## Appendix: Quick commands

```bash
# Re-bake SVGs locally
php artisan svg:bake 07

# Check SVG count in JSON
python3 -c "import json; d=json.load(open('storage/app/tasks/topic_07.json')); print(sum(1 for b in d['blocks'] for z in b['zadaniya'] for t in z['tasks'] if t.get('svg')))"

# Verify answer completeness
python3 -c "import json; d=json.load(open('storage/app/tasks/topic_07.json')); missing=[f\"T{t['id']}\" for b in d['blocks'] for z in b['zadaniya'] for t in z['tasks'] if not t.get('answer') and t.get('answer') != 0]; print(f'{len(missing)} missing answers' if missing else 'All answers present')"

# Verify count_integers T5 manually: 6^7 = 279936, 7^6 = 117649
python3 -c "a,b=6**7,7**6; lo,hi=min(a,b),max(a,b); print(f'{lo}..{hi}, count={hi-lo-1}')"

# Production deploy check
curl -s -o /dev/null -w '%{http_code}' https://cw95865.tmweb.ru/test/7
```
