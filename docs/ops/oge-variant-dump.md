# OGE Variant Dump Script

## Command

```bash
php scripts/oge_variant_dump.php <variant_id> > /tmp/oge_variant_report.json
```

## Example

```bash
php scripts/oge_variant_dump.php 8 > /tmp/oge_variant_8_report.json
```

## Output

JSON report with:
- variant metadata
- attempts summary
- per-attempt student/status/timestamps
- per-task answers/timings/scoring
- away-time totals from meta and events
