# eTaxWare API

Operational integration API for ERP-to-EFRIS workflows.

## Working Standards Checklist

Use this checklist for every change in this repository.

1. Keep naming consistent: use `eTaxWare` or `etaxware` only.
2. Preserve dual payload support: JSON and XML.
3. Prefer graceful fail-fast envelopes for payload mismatch scenarios.
4. Keep operator-facing messages concise.
5. Keep diagnostic detail in server logs.
6. Add PHP docblocks to newly added functions.
7. Add timestamped inline commentary for non-trivial logic blocks.
8. Update `docs/etaxware-api-specification.md` after every code change.
9. Validate changed files for diagnostics before closing work.
10. If runtime adapter is switched for testing, restore intended runtime before completion.

## Agent Instruction Sources

Repository-level instructions are persisted in:

- `.github/copilot-instructions.md`
- `AGENTS.md`

These files are the source of truth for coding standards in this repo.
