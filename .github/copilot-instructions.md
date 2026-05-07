# eTaxWare API Copilot Instructions

Apply these rules for all work in this repository.

## Product Naming
- Use only eTaxWare or etaxware casing.
- Do not use Etaxware.

## Change Workflow
- After every code change, update the API documentation in docs/etaxware-api-specification.md.
- Add a new changelog row in section 0 with version, date, and clear summary.
- Keep the "Current active adapter" note accurate if runtime/autoload changes are made.

## Code Commentary Standard
- For new or modified logic blocks, include timestamped inline maintainer commentary where helpful.
- Preferred commentary format (required):

/**
 * Modification Date: YYYY-MM-DD
 * Modified By: Francis Lubanga
 * Description: Short explanation of the change.
 */

## Function Documentation Standard
- Add PHP-style function documentation on top of newly added functions.
- For modified functions, improve docblocks when missing or unclear.

## Payload Compatibility Rules
- Preserve dual protocol support: JSON and XML.
- Do not replace JSON support when adding XML behavior.
- Use graceful fail-fast envelopes for payload mismatch scenarios.
- Keep operator-facing messages concise and actionable.
- Keep technical diagnostics detailed in server logs.

## Versioned Adapter Norm
- Respect version-folder conventions under api/<Adapter>/vN and util/<Adapter>/vN.
- Do not remove or overwrite previous versions unless explicitly requested.

## Validation Expectations
- Run file diagnostics after edits.
- When integration behavior changes, run at least one practical request simulation before finalizing.
- If runtime is switched for testing, restore intended active runtime before completion.
