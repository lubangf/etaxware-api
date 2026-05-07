# Agent Rules for etaxware-api

These repository rules are mandatory for coding agents.

1. Follow .github/copilot-instructions.md as the primary project standard.
2. Keep edits minimal and scoped to the request.
3. Update docs/etaxware-api-specification.md after each code change.
4. Use timestamped inline commentary for non-trivial logic changes with exact template keys: Modification Date, Modified By, Description.
5. Use PHP-style function docblocks for newly added functions.
6. Preserve compatibility behavior unless explicitly asked to break it.
7. Validate changed files for diagnostics before closing tasks.
8. Keep runtime/autoload state aligned with requested active adapter at task end.
