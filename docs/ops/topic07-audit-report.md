# Topic 07 Audit Report (A1-A2)

Date: 2026-03-02  
Scope: deterministic inventory + inequality logic consistency for `storage/app/tasks/topic_07.json`

## Artifacts

- Inventory script: `scripts/topic07_inventory.py`
- Logic checker: `scripts/topic07_logic_check.py`
- Source audited: `storage/app/tasks/topic_07.json`

## How to Reproduce

```bash
python3 scripts/topic07_inventory.py --format json > /tmp/topic07_inventory.json
python3 scripts/topic07_logic_check.py --format json > /tmp/topic07_logic.json
```

## Inventory Snapshot

- Total mapped rows (`block/zadanie/task_id`): `137`
- Task-level rows: `127`
- Zadanie-level rows: `10`
- Rows with SVG present (`task.svg` or `zadanie.svg`): `60`
- Rows without SVG: `77`
- Rows with missing explicit answer: `10` (all are `simple_choice` zadanie-level records)

## Logic Mismatches Found (Inequality Tasks)

Source: `scripts/topic07_logic_check.py`  
Total mismatches: `8`  
Severity split: `high=2`, `medium=6`, `low=0`

| Severity | Block | Zadanie | Task | Code | Details |
|---|---:|---:|---:|---|---|
| high | 1 | 2 | 3 | `answer_does_not_match_truth` | `Answer=1, true_options=[3, 4]` |
| high | 2 | 1 | 1 | `answer_does_not_match_truth` | `Answer=1, true_options=[2]` |
| medium | 1 | 1 | 6 | `non_unique_true_options` | `Expected exactly one true option, got [1, 2, 4]` |
| medium | 1 | 2 | 1 | `non_unique_true_options` | `Expected exactly one true option, got [1, 2, 3, 4]` |
| medium | 1 | 2 | 2 | `non_unique_true_options` | `Expected exactly one true option, got [1, 4]` |
| medium | 1 | 2 | 3 | `non_unique_true_options` | `Expected exactly one true option, got [3, 4]` |
| medium | 1 | 2 | 5 | `non_unique_true_options` | `Expected exactly one true option, got [1, 2, 3, 4]` |
| medium | 1 | 2 | 6 | `non_unique_true_options` | `Expected exactly one true option, got [1, 2, 3, 4]` |

## Priority Buckets

### P1 (Fix next)

1. `B1/Z2/T3` incorrect answer index vs evaluated true options.
2. `B2/Z1/T1` incorrect answer index vs evaluated true options.

### P2 (Content quality / ambiguity cleanup)

1. `B1/Z1/T6` has 3 true options.
2. `B1/Z2/T1` has 4 true options.
3. `B1/Z2/T2` has 2 true options.
4. `B1/Z2/T3` has 2 true options.
5. `B1/Z2/T5` has 4 true options.
6. `B1/Z2/T6` has 4 true options.

### P3 (Follow-up review)

1. `10` zadanie-level `simple_choice` entries have no explicit `answer` field in JSON and should be reviewed for scoring path expectations.

## SVG Persistence Note (Operational)

Rendered SVG must persist in the baked JSON on server:

- canonical file: `storage/app/tasks/topic_07.json`
- after bake/deploy, verify that `svg` fields are present for required tasks in this file and are not replaced by stale/non-baked payloads.

