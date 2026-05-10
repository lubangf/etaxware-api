# eTaxWare API Specification

Last updated: 2026-05-09

## 0. Version and Change Tracking

| Version | Date | Changes |
| --- | --- | --- |
| 2.1.28 | 2026-05-09 | Added XML response payload samples for all implemented endpoints in section 5.2.6 and added missing Section 10 JSON examples for `/checktaxpayer`, `/voidcreditnote`, and `/` (index). |
| 2.1.27 | 2026-05-09 | Added Section 10 JSON examples (request, curl, and sample responses) for `/fetchproduct`, `/stockin`, `/stockout`, and `/stocktransfer`; renumbered Section 10 sequence to keep contiguous ordering. |
| 2.1.26 | 2026-05-09 | Added Section 10 JSON examples (request, curl, and sample responses) for `/batchstockin`, `/batchstockout`, and `/batchstocktransfer`, including line-level `summary` and `lineResults` outcomes. |
| 2.1.25 | 2026-05-09 | Documented line-level batch response contracts for `/batchstockin`, `/batchstockout`, and `/batchstocktransfer` (`data.summary`, `data.lineResults`), added `/batchstocktransfer` endpoint documentation, and aligned route catalog/validation notes with current v15 behavior. |
| 2.1.24 | 2026-05-07 | Replaced remaining empty placeholder values in Section 10 sample payloads/responses (including `/validatetin`, `/queryinvoice`, `/uploadproduct`, and `/uploadinvoice`) with inferred dummy data. |
| 2.1.23 | 2026-05-07 | Replaced empty sample values in Section 10 `/uploadcreditnote` and `/uploaddebitnote` full payload examples with inferred dummy data for clearer implementation guidance. |
| 2.1.22 | 2026-05-07 | Expanded Section 10 `/uploadcreditnote` and `/uploaddebitnote` sample request/curl payloads with fuller business fields for production-like examples. |
| 2.1.21 | 2026-05-07 | Added sample success JSON responses for Section 10 `/uploadcreditnote` and `/uploaddebitnote` examples. |
| 2.1.20 | 2026-05-07 | Added Section 10 JSON examples (request, curl, and sample response) for `/uploadcreditnote` and `/uploaddebitnote`. |
| 2.1.19 | 2026-05-07 | Enforced mandatory `VOUCHERNUMBER` validation for `/stocktransfer` on both active Tally v18 XML and FTS v14 JSON handlers; requests without voucher number now fail fast with `-999` before duplicate-check/transfer submission. |
| 2.1.18 | 2026-05-07 | Added explicit SmartLogger log-routing rules section in operations docs to permanently define API and utility trace vs operational log destinations. |
| 2.1.17 | 2026-05-07 | Refined Tally v18 SmartLogger trace routing by updating the `beforeroute` raw-body log phrase to match trace classification rules, ensuring XML payload body lines are written to `api-trace.log` instead of `api.log`. |
| 2.1.16 | 2026-05-07 | Added SmartLogger to Tally runtime on upgraded paths (`api/Tally/v18`, `util/Tally/v5`) so high-volume trace messages are routed to `api-trace.log`/`util-trace.log` while operational messages remain in `api.log`/`util.log`. |
| 2.1.15 | 2026-05-07 | Upgraded Tally runtime version paths ahead of SmartLogger rollout: cloned `api/Tally/v17` to `api/Tally/v18` and `util/Tally/v4` to `util/Tally/v5`, then switched `AUTOLOAD` to the new versioned folders without changing runtime behavior. |
| 2.1.14 | 2026-05-07 | Added stocktransfer duplicate-upload control in FTS v14 using `VOUCHERNUMBER` pre-check against `tblgoodsstocktransfer`; successful FTS stocktransfer logging now persists voucher metadata (`VOUCHERTYPE`, `VOUCHERTYPENAME`, `VOUCHERNUMBER`, `VOUCHERREF`) for replay protection parity with Tally flow. |
| 2.1.13 | 2026-05-07 | Added stocktransfer duplicate-upload control in Tally v17 using `VOUCHERNUMBER` pre-check against persisted stock transfer logs (`tblgoodsstocktransfer`); successful stocktransfer logging now persists voucher metadata (`VOUCHERTYPE`, `VOUCHERTYPENAME`, `VOUCHERNUMBER`, `VOUCHERREF`) to enforce future duplicate checks. |
| 2.1.12 | 2026-05-07 | Hardened Tally `stocktransfer` flow to initialize transfer context fields and prevent undefined-variable usage in transfer logs/audit activity; derived product/quantity context from source inventories for deterministic logging. |
| 2.1.11 | 2026-05-07 | Added catch-all unsupported-format fail-fast handling (`1093`) for payloads that are neither valid JSON nor valid XML. Applies to both active FTS JSON adapter and Tally XML adapter while preserving existing dedicated mismatch codes (`1090`, `1091`, `1092`). |
| 2.1.10 | 2026-05-07 | Standardized persisted maintainer inline-commentary format in repo instruction files to explicitly use `Modification Date`, `Modified By`, and `Description` keys. |
| 2.1.9 | 2026-05-07 | Added repository governance checklist to `README.md` and persisted repo-level coding standards via agent instruction references for consistent future sessions. |
| 2.1.8 | 2026-05-07 | Added symmetric graceful fail-fast handling on XML adapter (`api/Tally/v17`) for JSON payloads: returns parser-friendly XML envelope with `RETURNCODE=1092` and concise guidance to use FTS endpoint. Includes detailed expected/received payload logging and end-to-end localhost verification; runtime restored to FTS v14 after test. |
| 2.1.7 | 2026-05-07 | Shortened operator-facing XML mismatch message (`1090`) for better Tally UI readability while retaining detailed expected/received payload diagnostics in server logs. |
| 2.1.6 | 2026-05-07 | Restored active runtime autoload to FTS v14 after XML envelope parser-compatibility patch validation on Tally adapter test flow. |
| 2.1.5 | 2026-05-07 | Aligned FTS graceful-fail XML envelope header status to `STATUS=1` (while keeping business error codes/messages in `RETURNCODE` and `RETURNMESSAGE`) for Tally parser compatibility. |
| 2.1.4 | 2026-05-07 | Switched active runtime autoload to Tally adapter (`api/Tally/v17`, `util/Tally/v4`) to validate successful XML return-payload capture behavior in Tally test flow. |
| 2.1.3 | 2026-05-07 | Updated runtime documentation to reflect active FTS v14 baseline and documented code-maintenance standard for timestamped inline maintainer commentary and PHPDoc on newly introduced helper functions. |
| 2.1.2 | 2026-05-07 | Documented XML request support for Tally ERP 9 and Tally Prime and added sample XML payloads for each implemented endpoint. |
| 2.1.1 | 2026-04-26 | Clarified and enforced v13 credit-note reason behavior: `reasonCode` is normalized via mapping, while `reason` is preserved as ERP-provided free text (no reason-text mapping). |
| 2.1.0 | 2026-04-26 | Promoted runtime baseline from v12 to v13 to isolate incoming change-set work while preserving v12 behavior as fallback. |
| 2.0.9 | 2026-04-26 | Added runtime/operations documentation for graceful HTTP error JSON envelopes, GET health-check support on `/`, and automatic log rotation with trace-log split (`api-trace.log`, `util-trace.log`). |
| 2.0.8 | 2026-04-25 | Added consolidated error-code glossary with meanings, typical source areas, and interpretation notes for integrators/support teams. |
| 2.0.7 | 2026-04-25 | Added business glossary for onboarding and shared business-language alignment between ERP, eTaxWare, and EFRIS users. |
| 2.0.6 | 2026-04-25 | Added v12 mapping normalization across stock and persistence flows: mapped stock-in/out and adjustment type values before outbound calls and local logs, mapped stock transfer branch/product persistence values, and mapped persisted credit-note `currency`, `invoiceindustrycode`, and `reasoncode`. |
| 2.0.5 | 2026-04-25 | Applied stock-in unit-price hardening: resolve request/product pricing (`UNITPRICE` -> `purchaseprice` -> `unitprice`) and return `659` when unresolved. |
| 2.0.4 | 2026-04-25 | Removed stock-out unit price fallback to `1`; stock-out now resolves product pricing (`purchaseprice`/`unitprice`) or returns explicit pricing error. |
| 2.0.3 | 2026-04-25 | Added returned-fields tables for all documented implemented endpoints in section 5.2. |
| 2.0.2 | 2026-04-25 | Enforced mandatory `VOUCHERNUMBER` validation for `/batchstockout` with explicit fail-fast response. |
| 2.0.1 | 2026-04-25 | Enforced mandatory `VOUCHERNUMBER` validation for `/batchstockin` with explicit fail-fast response. |
| 2.0.0 | 2026-04-25 | Promoted runtime baseline from v11 to v12 and started v12 issue-fix track. |
| 1.3.0 | 2026-04-25 | Added endpoint-level curl examples for the section 10 sample payloads. |
| 1.2.0 | 2026-04-25 | Added extensive sample request/response payloads and cleaned markdown structure. |
| 1.1.0 | 2026-04-25 | Hardened `/queryinvoice` behavior notes and expanded endpoint validation documentation. |
| 1.0.0 | 2026-04-25 | Initial API specification baseline and routed endpoint catalog. |

## 1. Overview

This guide documents the API endpoints in the etaxware-api service.

- Runtime adapter: `api/{adapter}/Api.php`
- Current active adapter (config): `api/Tally/v18/Api.php`
- Route map source: `config/routes.ini`
- Utility adapter: `util/{adapter}/Utilities.php`
- Protocol: JSON and XML over HTTP
- Route methods: POST for business endpoints, plus GET support on `/` for health/browser probing.

For Tally ERP 9 and Tally Prime integrations, XML payloads are supported as first-class request bodies. JSON payloads remain supported for non-Tally integrations and tooling.

### 1.1 Business Context

etaxware-api is the ERP-facing integration layer for eTaxWare tax operations. Its business role is to translate ERP transactions into compliant URA EFRIS interactions while preserving ERP identifiers and operational auditability.

Business outcomes supported by this API:

- Revenue compliance: submits and reconciles invoices, credit notes, debit notes, and stock movements against EFRIS requirements.
- Operational continuity: allows ERP users to keep their native voucher/journal processes while enforcing tax-platform rules.
- Data consistency: stores and normalizes mapped business values (for example branch, product, stock type, adjustment type, currency, reason, industry) to reduce integration drift between ERP and EFRIS domains.
- Traceability and supportability: captures audit metadata (`ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME`) for troubleshooting and compliance evidence.

### 1.2 Business Operating Model

This API operates as one entry point in a shared business platform:

- `etaxware-api`: machine-to-machine adapter used by ERP systems.
- `etaxware`: browser-facing operational application used by internal users.
- Shared persistence: both runtimes use the same `etaxware` database model for document lifecycle and reference data.

Practical implications for integrators:

- Most transaction failures are business-level validations returned in the response envelope while HTTP remains `200`.
- Successful integrations depend on both technical auth fields and business master-data readiness (product, branch, tax, reason, and mapping dictionaries).
- Endpoint behavior can include business normalization before persistence and before outbound calls to EFRIS.

### 1.3 Business Glossary

| Term | Business meaning in this API context |
| --- | --- |
| eTaxWare | The internal tax operations platform where business documents, mappings, settings, and audit artifacts are persisted and managed. |
| etaxware-api | The ERP-facing integration layer that receives ERP transactions and orchestrates compliant EFRIS interactions. |
| EFRIS | Uganda Revenue Authority (URA) tax platform used for tax document submission, validation, and query workflows. |
| Voucher | ERP business document identity (for example invoice, credit note, stock journal) represented by `VOUCHERNUMBER`, `VOUCHERREF`, `VOUCHERTYPE`, and `VOUCHERTYPENAME`. |
| ORGTIN | Integrated organization TIN used as tenant/business identity guard at pre-route validation time. |
| ERPUSER | ERP user identity used to resolve permissions and ownership context for transactions and audit logs. |
| Business response code | The value in `response.responseCode` representing business outcome; may indicate failure even when HTTP status is `200`. |
| Mapping normalization | Translation of ERP-facing labels/codes (for example branch, stock types, adjustment types, currency, reasons) into canonical values used for persistence and EFRIS requests. |
| Stock in type | Business reason/category for increasing stock (for example purchase, manufacture); validated and mapped before request/persistence. |
| Stock adjustment type | Business reason/category for reducing or adjusting stock; validated and mapped before request/persistence. |
| Master-data readiness | Operational prerequisite that required dictionaries and reference records (products, taxes, branches, mappings) exist and are aligned before posting transactions. |
| Audit metadata | Request identity telemetry (`WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME`) stored for traceability, support, and compliance evidence. |

## 2. Base URL and Transport

Use your deployment base, for example:

- Local XAMPP: `http://localhost/etaxware-api`

Most routes in the active adapter are POST routes. Root `/` also accepts GET for health/browser probing.

## 3. Authentication and Pre-route Validation

All requests pass through `beforeroute()` and are rejected early when required fields are missing or invalid.

### 3.1 Required common request fields

Include these in every body payload:

- `ORGTIN`: integrated company TIN
- `APIKEY`: active API key
- `VERSION`: must match server `APPVERSION`
- `ERPUSER`: ERP user code used to resolve user and permissions
- `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME`: used in audit logs

Notes:

- `APIKEY` can also be sent as query parameter `?apikey=...`, but body value is preferred.
- Requests with empty body and no query apikey are rejected.

### 3.2 Core pre-route error codes

- `1000`: no parameters sent
- `7650`: missing company TIN (`ORGTIN`)
- `7651`: supplied TIN does not match integrated TIN
- `1001`: missing API key
- `1002`: API key invalid, inactive, or expired
- `1003`: plugin version mismatch

### 3.3 Error Code Glossary

Use this glossary when interpreting `response.responseCode` values in endpoint responses.

| Code | Meaning | Typical source area | Integration note |
| --- | --- | --- | --- |
| `00` | Operation successful | Most endpoints | Business success indicator; still check returned `data` content for context-specific values. |
| `45` | Partial/mixed batch stock result | `/batchstockin`, `/batchstockout`, `/batchstocktransfer` | Indicates at least one line did not complete cleanly; inspect response message and line-level outcomes. |
| `99` | Generic business failure or record state conflict | Multiple endpoints | Commonly used for not found/already processed/operation not successful conditions. |
| `0099` | User not allowed to perform function | Permission checks in endpoint handlers | Caller authenticated but lacks required operation permission. |
| `-999` | Mandatory business field missing/invalid | Multiple endpoint validators | Common fail-fast validation code (for example missing voucher number, missing reason, missing TIN). |
| `-997` | Missing line-level required fields | Batch stock validators | One or more batch inventory lines are missing required `PRODUCTCODE` and/or `QTY`. |
| `-998` | Commodity code missing | `/checktaxpayer` validator | Specific validation for missing `COMMODITYCODE`. |
| `1000` | No parameters sent | Pre-route gate | Request body/query key envelope is missing or empty. |
| `1001` | API key missing | Pre-route gate | Missing `APIKEY` in body/query. |
| `1002` | API key invalid/inactive/expired | Pre-route gate | Key failed validity checks against configured API key store. |
| `1003` | Plugin version mismatch | Pre-route gate | Caller `VERSION` does not match configured server app version. |
| `1006` | Unsupported voucher type | `/queryinvoice` voucher routing | Voucher type label did not match supported invoice/credit/debit families. |
| `300` | Email send failure | `/sendmail` | SMTP/config/send operation failed. |
| `500` | Email request missing required content | `/sendmail` | Missing recipient email and/or message body. |
| `602` | Goods code already exists | Product upload passthrough from upstream tax platform | Business duplicate conflict; implementation may still sync local state if remote already has product. |
| `659` | Unit price could not be resolved | Stock in/out flows | Pricing resolution failed after fallback sequence (request/product pricing). |
| `7650` | Integrated company TIN missing | Pre-route gate | Required `ORGTIN` not provided. |
| `7651` | Supplied TIN does not match integrated TIN | Pre-route gate | Caller tenant identity mismatch against configured organization TIN. |
| `2398` | Product tax code missing | `/uploadproduct` validator | Product upload blocked because `TAXCODE` was not supplied. |

Notes:

- Envelope codes are business-level outcomes and are the primary source of integration state, even when HTTP status is `200`.
- Some non-listed codes may be passed through from upstream EFRIS responses; treat unknown codes as upstream/business exceptions and log full response payload for triage.

## 4. Standard Response Envelope

Most endpoints return this shape:

```json
{
  "response": {
    "responseCode": "00",
    "responseMessage": "..."
  },
  "data": {}
}
```

`data` may be an object, list, or empty array depending on endpoint.

### 4.1 HTTP Failure Envelope (404/405/5xx)

Framework-level HTTP failures are returned as graceful JSON (instead of raw framework HTML).

Example shape:

```json
{
  "response": {
    "responseCode": "405",
    "responseMessage": "Method Not Allowed. Use POST for API endpoints. For health checks, GET / is supported."
  },
  "data": {
    "method": "GET",
    "path": "/etaxware-api/uploadinvoice"
  }
}
```

Notes:

- HTTP status code is preserved at transport level (`404`, `405`, `500`, etc.).
- For 5xx failures, response messages are sanitized for clients while details remain in server logs.

### 4.2 Operational Logging and Rotation

The runtime now auto-manages log growth and separates high-volume traces from operational signals.

Primary logs:

- `error.log` (bootstrap/framework errors)
- `api.log` (operational API controller logs)
- `util.log` (operational utility/service logs)

Trace logs:

- `api-trace.log` (request/response/raw body/URL-style diagnostics)
- `util-trace.log` (request/response/SQL-style diagnostics)

Automatic rotation:

- Rotation runs during bootstrap (no manual GUI trigger required).
- Size threshold and retention are config-driven:
  - `log_rotation_enabled`
  - `log_rotate_max_mb`
  - `log_rotate_max_files`
- Rotated files use timestamp suffixes (for example `util-trace.log.YYYYMMDD-HHMMSS`).

### 4.2.1 SmartLogger Routing Rules

Use these rules as the canonical expected behavior for SmartLogger-enabled runtimes:

- Payload trace content for new vouchers routes to `api-trace.log` and not to `api.log`.
- Operational API messages (for example permission, version, and user-context messages) remain in `api.log`.
- Utility high-volume request and response diagnostics route to `util-trace.log`.
- Utility operational and status messages remain in `util.log`.

Validation reference:

- Rules above were validated against live stocktransfer requests on 2026-05-07 after the Tally v18/v5 SmartLogger rollout.

## 5. Endpoint Catalog

### 5.1 Routed endpoints from config/routes.ini

| Route | Handler | Runtime status |
| --- | --- | --- |
| `/` | `Api->index` | Implemented (GET + POST) |
| `/testapi` | `Api->testapi` | Implemented |
| `/uploadproduct` | `Api->uploadproduct` | Implemented |
| `/stockin` | `Api->stockin` | Implemented |
| `/stockout` | `Api->stockout` | Implemented |
| `/fetchproduct` | `Api->fetchproduct` | Implemented |
| `/batchstockin` | `Api->batchstockin` | Implemented |
| `/uploadcommoditycode` | `Api->uploadcommoditycode` | Not implemented in current Api class |
| `/batchstockout` | `Api->batchstockout` | Implemented |
| `/batchstocktransfer` | `Api->batchstocktransfer` | Implemented |
| `/stocktransfer` | `Api->stocktransfer` | Implemented |
| `/uploadinvoice` | `Api->uploadinvoice` | Implemented |
| `/queryinvoice` | `Api->queryinvoice` | Implemented |
| `/printinvoice` | `Api->printinvoice` | Not implemented in current Api class |
| `/validatetin` | `Api->validatetin` | Implemented |
| `/checktaxpayer` | `Api->checktaxpayer` | Implemented |
| `/currencyquery` | `Api->currencyquery` | Implemented |
| `/uploadcreditnote` | `Api->uploadcreditnote` | Implemented |
| `/querycreditnote` | `Api->querycreditnote` | Not implemented in current Api class |
| `/voidcreditnote` | `Api->voidcreditnote` | Implemented |
| `/uploaddebitnote` | `Api->uploaddebitnote` | Implemented |
| `/querydebitnote` | `Api->querydebitnote` | Not implemented in current Api class |
| `/loadrateunits` | `Utilities->loadrateunits` | Not implemented in current Utilities class |
| `/sendmail` | `Api->sendmail` | Implemented |
| `/uploadcustomer` | `Api->uploadcustomer` | Not implemented in current Api class |
| `/uploadsupplier` | `Api->uploadsupplier` | Not implemented in current Api class |
| `/uploadimportedservice` | `Api->uploadimportedservice` | Not implemented in current Api class |

### 5.2 Implemented endpoint details

### 5.2.0 XML Quick Reference (Tally ERP 9 / Tally Prime)

All XML requests use the root `<REQUEST>...</REQUEST>` and include the common envelope fields from section 3.1:
`VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME`.

Important naming rule:

- JSON technical field names and XML tag names may differ for some endpoints.
- When names differ, they still represent the same business meaning.
- The JSON technical name remains the canonical reference in parameter tables, while XML equivalents are shown in this section and in endpoint XML samples.

JSON-to-XML semantic equivalence (known endpoint differences):

| Endpoint | JSON technical field name(s) | XML equivalent tag/path | Same business meaning |
| --- | --- | --- | --- |
| `/batchstockin` | `INVENTORIES[]` | `DESTINATIONINVENTORIES/INVENTORY` | Batch destination stock line collection |
| `/stocktransfer` | `SOURCEBRANCH` | `SOURCEINVENTORIES/INVENTORY/LOCATION` | Source branch |
| `/stocktransfer` | `DESTBRANCH` | `DESTINATIONINVENTORIES/INVENTORY/LOCATION` | Destination branch |
| `/stocktransfer` | `PRODUCTCODE`, `QTY` | `SOURCEINVENTORIES/INVENTORY/PRODUCTCODE`, `SOURCEINVENTORIES/INVENTORY/QTY` | Product and quantity to transfer |
| `/batchstocktransfer` | `SOURCEBRANCH` | `SOURCEBRANCH` (or source inventory location context) | Source branch |
| `/batchstocktransfer` | `DESTBRANCH` | `DESTBRANCH` (or destination inventory location context) | Destination branch |
| `/batchstocktransfer` | `INVENTORIES[]` (`PRODUCTCODE`, `QTY`) | `INVENTORIES/INVENTORY` (`PRODUCTCODE`, `QTY`) | Batch transfer line collection |

Endpoint-specific XML nodes:

| Endpoint | Required endpoint-specific XML nodes | Common optional XML nodes |
| --- | --- | --- |
| `/` | None | None |
| `/testapi` | None | None |
| `/validatetin` | `TIN` | None |
| `/checktaxpayer` | `TIN`, `COMMODITYCODE` | None |
| `/currencyquery` | None | None |
| `/uploadproduct` | `ITEMNAME`, `ITEMID`, `PRODUCTCODE`, `COMMODITYCODE`, `MEASUREUNITS`, `CURRENCY`, `UNITPRICE`, `TAXCODE` | `HASEXCISEDUTYFLAG`, `EXCISEDUTYCODE`, `HAVEPIECEUNITSFLAG`, `PIECEUNITSMEASUREUNIT`, `PIECEUNITPRICE`, `PACKAGESCALEVALUE`, `PIECESCALEVALUE`, `HSCODE`, `CUSTOMMEASUREUNIT`, `CUSTOMUNITPRICE`, `CUSTOMPACKAGESCALEDVALUE`, `CUSTOMSCALEDVALUE`, `CUSTOMWEIGHT` |
| `/fetchproduct` | `PRODUCTCODE` | `ERPQTY` |
| `/stockin` | `PRODUCTCODE`, `STOCKINTYPE`, `UNITPRICE`, `QTY` | `PRODUCTIONDATE`, `BATCHNUMBER`, `SUPPLIERTIN`, `SUPPLIERNAME` |
| `/batchstockin` | `STOCKINTYPE`, `VOUCHERTYPE`, `VOUCHERTYPENAME`, `VOUCHERNUMBER`, `VOUCHERREF`, `DESTINATIONINVENTORIES/INVENTORY` (with `PRODUCTCODE`, `QTY`, `RATE`) | `INVENTORIES/INVENTORY` (legacy naming in some clients), `PRODUCTIONDATE`, `SUPPLIERTIN`, `SUPPLIERNAME` |
| `/stockout` | `PRODUCTCODE`, `ADJUSTMENTTYPE`, `QTY` | `REMARKS`, `BATCHNUMBER` |
| `/batchstockout` | `VOUCHERTYPE`, `VOUCHERTYPENAME`, `VOUCHERNUMBER`, `VOUCHERREF`, `INVENTORIES/INVENTORY` (with `PRODUCTCODE`, `QTY`) | `RATE` (inventory line), `ADJUSTMENTTYPE`, `REMARKS` |
| `/batchstocktransfer` | `VOUCHERNUMBER`, `SOURCEBRANCH`, `DESTBRANCH`, `INVENTORIES/INVENTORY` (with `PRODUCTCODE`, `QTY`) | `REMARKS`, `VOUCHERREF`, `VOUCHERTYPE`, `VOUCHERTYPENAME` |
| `/stocktransfer` | `VOUCHERNUMBER`, `SOURCEINVENTORIES/INVENTORY` (with `PRODUCTCODE`, `QTY`, `LOCATION`), `DESTINATIONINVENTORIES/INVENTORY` (with `LOCATION`) | `PRODUCTCODE`, `SOURCEBRANCH`, `DESTBRANCH`, `QTY`, `REMARKS` (legacy/flat naming), `STOCKITEMNAME`, `RATE`, `VOUCHERREF`, `VOUCHERTYPE`, `VOUCHERTYPENAME` |
| `/uploadinvoice` | `VOUCHERTYPE`, `VOUCHERTYPENAME`, `VOUCHERNUMBER`, `VOUCHERREF`, `CURRENCY`, `INDUSTRYCODE`, `BUYERLEGALNAME`, `BUYERCITIZENSHIP`, `BUYERTYPE`, `INVENTORIES/INVENTORY` | `PRICEVATINCLUSIVE`, `PROJECTID`, `PROJECTNAME`, `DELIVERYTERMCODE`, `NONRESIDENTFLAG` |
| `/uploadcreditnote` | `VOUCHERTYPE`, `VOUCHERTYPENAME`, `VOUCHERNUMBER`, `VOUCHERREF`, `ORIVOUCHERNUMBER`, `REASONS/REASON` (`REASONCODE`, `REASON`), `INVENTORIES/INVENTORY` | None |
| `/uploaddebitnote` | `VOUCHERTYPE`, `VOUCHERTYPENAME`, `VOUCHERNUMBER`, `VOUCHERREF`, `ORIVOUCHERNUMBER`, `REASONS/REASON` (`REASONCODE`, `REASON`), `INVENTORIES/INVENTORY` | None |
| `/queryinvoice` | `VOUCHERNUMBER`, `VOUCHERREF`, `VOUCHERTYPE`, `VOUCHERTYPENAME` | None |
| `/voidcreditnote` | `VOUCHERNUMBER`, `VOUCHERREF`, `VOUCHERTYPE`, `VOUCHERTYPENAME` | None |
| `/sendmail` | `RECIPIENTEMAIL`, `BODY` | `RECIPIENTNAME`, `SUBJECT` |

### 5.2.1 Health and diagnostics

#### GET or POST /

- Purpose: service health check
- Handler: `index()`
- Typical success: `00`, `It Works!` (POST with valid standard envelope)
- Browser/quick probe behavior: GET `/` without required request payload returns graceful JSON validation response (for example `1000: No parameters were sent!`) instead of framework error HTML.

Sample XML payload (for POST):

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally Prime</SYSTEMNAME>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` |
| response.responseMessage | API outcome message | Yes | `It Works!` |
| data | Endpoint payload container | Yes | `[]` |

#### POST /testapi (XML sample)

- Purpose: service health check (alternate)
- Handler: `testapi()`
- Typical success: `00`, `It Works!`

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally ERP 9</SYSTEMNAME>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` |
| response.responseMessage | API outcome message | Yes | `It Works!` |
| data | Endpoint payload container | Yes | `[]` |

### 5.2.2 Taxpayer and currency lookups

#### POST /validatetin (XML sample)

- Purpose: validate and fetch taxpayer details
- Permission: `QUERYTAXPAYER`
- Required fields (endpoint-specific):
  - `TIN`
- Success data fields:
  - `NINBRN`, `LEGALNAME`, `BUSINESSNAME`, `CONTACTNUMBER`, `CONTACTEMAIL`, `ADDRESS`
- Common business errors:
  - `-999` when TIN is missing

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally Prime</SYSTEMNAME>
  <TIN>9083746521</TIN>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| TIN | Taxpayer Identification Number to validate | Yes | `9083746521` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `-999` |
| response.responseMessage | API outcome message | Yes | `The operation to query the taxpayer was successful` |
| data.NINBRN | National ID/BRN | Conditional | `/80020002851201` |
| data.LEGALNAME | Taxpayer legal name | Conditional | `FTS GROUP CONSULTING SERVICES LIMITED` |
| data.BUSINESSNAME | Taxpayer business name | Conditional | `FTS GROUP CONSULTING SERVICES LIMITED` |
| data.CONTACTNUMBER | Contact phone | Conditional | `` |
| data.CONTACTEMAIL | Contact email | Conditional | `` |
| data.ADDRESS | Taxpayer address | Conditional | `` |

#### POST /checktaxpayer (XML sample)

- Purpose: determine taxpayer type and commodity taxpayer profile
- Permission: `QUERYTAXPAYER`
- Required fields:
  - `TIN`
  - `COMMODITYCODE`
- Success data fields:
  - `TAXPAYERTYPE`, `COMMODITYTAXPAYERTYPE`
- Common business errors:
  - `-999` missing TIN
  - `-998` missing commodity code

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally Prime</SYSTEMNAME>
  <TIN>9083746521</TIN>
  <COMMODITYCODE>101</COMMODITYCODE>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| TIN | Taxpayer Identification Number | Yes | `9083746521` |
| COMMODITYCODE | Commodity classification code | Yes | `101` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `-998` / `-999` |
| response.responseMessage | API outcome message | Yes | `The operation to check taxpayer was successful` |
| data.TAXPAYERTYPE | Taxpayer type code | Conditional | `1` |
| data.COMMODITYTAXPAYERTYPE | Commodity taxpayer type code | Conditional | `2` |

#### POST /currencyquery (XML sample)

- Purpose: fetch configured currency rates
- Permission: `FETCHCURRENCYRATES`
- Endpoint-specific fields: none beyond common auth/audit metadata
- Success data:
  - Dynamic object map, example `{ "UGX": "1", "USD": "..." }`

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally ERP 9</SYSTEMNAME>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` |
| response.responseMessage | API outcome message | Yes | `The operation to fetch the currencies was successful` |
| data.<CURRENCY_CODE> | Exchange rate by currency code key | Conditional | `UGX: 1`, `USD: 3743.2163` |

### 5.2.3 Product and stock operations

#### POST /uploadproduct (XML sample)

- Purpose: create/update product in EFRIS and sync into local tables
- Permission: `UPLOADPRODUCT`
- Key required fields:
  - `ITEMNAME`, `ITEMID`, `MEASUREUNITS`
  - `COMMODITYCODE`, `PRODUCTCODE`, `CURRENCY`
  - `UNITPRICE`, `TAXCODE`
- Important optional/extended fields:
  - Excise: `HASEXCISEDUTYFLAG`, `EXCISEDUTYCODE`
  - Piece units: `HAVEPIECEUNITSFLAG`, `PIECEUNITSMEASUREUNIT`, `PIECEUNITPRICE`, `PACKAGESCALEVALUE`, `PIECESCALEVALUE`
  - Customs/HS: `HSCODE`, `CUSTOMMEASUREUNIT`, `CUSTOMUNITPRICE`, `CUSTOMPACKAGESCALEDVALUE`, `CUSTOMSCALEDVALUE`, `CUSTOMWEIGHT`
- Response `data` includes product classification flags:
  - `ISTAXEXEMPT`, `ISZERORATED`, `TAXRATE`, `STATUS`, `SOURCE`, `EXCLUSION`, `PRODID`, `SERVICEMARK`
- Common business errors:
  - `2398` when `TAXCODE` is missing

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally Prime</SYSTEMNAME>
  <ITEMNAME>Sample Product</ITEMNAME>
  <ITEMID>SP-100</ITEMID>
  <PRODUCTCODE>SP-100</PRODUCTCODE>
  <COMMODITYCODE>101</COMMODITYCODE>
  <MEASUREUNITS>Pcs</MEASUREUNITS>
  <CURRENCY>UGX</CURRENCY>
  <UNITPRICE>1000</UNITPRICE>
  <TAXCODE>01</TAXCODE>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| ITEMNAME | Product name | Yes | `Sample Product` |
| ITEMID | ERP item identifier | Yes | `SP-100` |
| PRODUCTCODE | Product code sent to EFRIS | Yes | `SP-100` |
| COMMODITYCODE | Commodity code | Yes | `101` |
| MEASUREUNITS | Unit of measure | Yes | `Pcs` |
| CURRENCY | Transaction currency | Yes | `UGX` |
| UNITPRICE | Unit selling price | Yes | `1000` |
| TAXCODE | Tax code | Yes | `01` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `2398` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data.ISTAXEXEMPT | Tax-exempt flag | Conditional | `0` |
| data.ISZERORATED | Zero-rated flag | Conditional | `0` |
| data.TAXRATE | Applied tax rate | Conditional | `0.18` |
| data.STATUS | Product status | Conditional | `Active` |
| data.SOURCE | Product source | Conditional | `EFRIS` |
| data.EXCLUSION | Exclusion status | Conditional | `` |
| data.PRODID | EFRIS product identifier | Conditional | `1419845115243627714` |
| data.SERVICEMARK | Service marker | Conditional | `102` |

#### POST /fetchproduct (XML sample)

- Purpose: query product from EFRIS and return tax/status profile
- Permission: `FETCHPRODUCT`
- Key fields:
  - `PRODUCTCODE`
  - `ERPQTY` (optional; normalized to `0` when omitted)
- Response `data` includes product tax/status indicators similar to uploadproduct response.

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally Prime</SYSTEMNAME>
  <PRODUCTCODE>SP-100</PRODUCTCODE>
  <ERPQTY>0</ERPQTY>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| PRODUCTCODE | Product code to query | Yes | `SP-100` |
| ERPQTY | ERP quantity context | No | `0` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `99` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data.PRODUCTCODE | Product code | Conditional | `SP-100` |
| data.ITEMNAME | Product name | Conditional | `Sample Product` |
| data.TAXRATE | Product tax rate | Conditional | `0.18` |
| data.STATUS | Product status | Conditional | `Active` |
| data.SERVICEMARK | Service marker | Conditional | `102` |

#### POST /stockin (XML sample)

- Purpose: add stock for one product
- Permission: `STOCKIN`
- Required fields:
  - `PRODUCTCODE`, `STOCKINTYPE`, `UNITPRICE`, `QTY`
- Conditional fields:
  - If `STOCKINTYPE == 103` (manufacture): `PRODUCTIONDATE`, `BATCHNUMBER`
  - Optional supplier validation: `SUPPLIERTIN`, `SUPPLIERNAME`
- Pricing behavior:
  - Unit price is resolved in order: request `UNITPRICE`, product `purchaseprice`, then product `unitprice`; no default `1` fallback.
- Mapping behavior:
  - `STOCKINTYPE` is normalized through stock-in type mappings before request dispatch and before local stock-adjustment persistence.

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally ERP 9</SYSTEMNAME>
  <PRODUCTCODE>SP-100</PRODUCTCODE>
  <STOCKINTYPE>101</STOCKINTYPE>
  <UNITPRICE>1000</UNITPRICE>
  <QTY>10</QTY>
  <SUPPLIERTIN>9083746521</SUPPLIERTIN>
  <SUPPLIERNAME>Sample Supplier</SUPPLIERNAME>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| PRODUCTCODE | Product code | Yes | `SP-100` |
| STOCKINTYPE | Stock-in type code | Yes | `101` |
| UNITPRICE | Unit price for stock entry | Yes | `1000` |
| QTY | Quantity to add | Yes | `10` |
| PRODUCTIONDATE | Production date (manufacture mode) | Conditional | `2026-04-25` |
| BATCHNUMBER | Batch number (manufacture mode) | Conditional | `BATCH-001` |
| SUPPLIERTIN | Supplier TIN | No | `9083746521` |
| SUPPLIERNAME | Supplier legal name | No | `Sample Supplier` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `99` / `659` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data | Endpoint payload container | Yes | `[]` |

#### POST /batchstockin (XML sample)

- Purpose: add stock for multiple products (batch)
- Permission: `STOCKIN`
- Required fields:
  - `STOCKINTYPE`, `VOUCHERTYPE`, `VOUCHERTYPENAME`, `VOUCHERNUMBER`, `VOUCHERREF`
  - `DESTINATIONINVENTORIES[]` with per-line `PRODUCTCODE`, `QTY`, `RATE`
- Compatibility fields:
  - `INVENTORIES[]` (legacy naming in some clients; equivalent intent to destination inventory lines)
- Conditional fields:
  - `PRODUCTIONDATE` used when `STOCKINTYPE == 103`
  - For non-103 types: optional `SUPPLIERTIN`, `SUPPLIERNAME`
- Behavior note:
  - Services (`serviceMark == 101`) are skipped in batch stockin.
  - `STOCKINTYPE` is normalized through stock-in type mappings before request dispatch and before local stock-adjustment persistence.

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally Prime</SYSTEMNAME>
  <STOCKINTYPE>101</STOCKINTYPE>
  <VOUCHERTYPE>Purchase</VOUCHERTYPE>
  <VOUCHERTYPENAME>Purchase</VOUCHERTYPENAME>
  <VOUCHERNUMBER>GRN-001</VOUCHERNUMBER>
  <VOUCHERREF>ERP-GRN-001</VOUCHERREF>
  <DESTINATIONINVENTORIES>
    <INVENTORY>
      <PRODUCTCODE>SP-100</PRODUCTCODE>
      <QTY>10</QTY>
      <RATE>1000</RATE>
    </INVENTORY>
  </DESTINATIONINVENTORIES>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| STOCKINTYPE | Stock-in type code | Yes | `101` |
| VOUCHERTYPE | Voucher type | Yes | `Purchase` |
| VOUCHERTYPENAME | Voucher type display name | Yes | `Purchase` |
| VOUCHERNUMBER | Voucher number | Yes | `GRN-001` |
| VOUCHERREF | Voucher reference | Yes | `ERP-GRN-001` |
| DESTINATIONINVENTORIES[] | Destination inventory line collection | Yes | `[{"PRODUCTCODE":"SP-100","QTY":"10","RATE":"1000"}]` |
| INVENTORIES[] | Inventory line collection (legacy naming) | Conditional | `[{"PRODUCTCODE":"SP-100","QTY":"10","RATE":"1000"}]` |
| PRODUCTIONDATE | Production date (when `STOCKINTYPE == 103`) | Conditional | `2026-04-25` |
| SUPPLIERTIN | Supplier TIN | No | `9083746521` |
| SUPPLIERNAME | Supplier name | No | `Sample Supplier` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `45` / `-999` / `-998` / `99` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data.voucherNumber | Voucher number echoed for correlation | Conditional | `GRN-001` |
| data.voucherRef | Voucher reference echoed for correlation | Conditional | `ERP-GRN-001` |
| data.stockInType | Stock-in type used for request | Conditional | `101` |
| data.summary.totalLines | Total input lines received | Conditional | `3` |
| data.summary.successCount | Number of successful lines | Conditional | `1` |
| data.summary.failureCount | Number of failed lines | Conditional | `2` |
| data.summary.skippedCount | Number of skipped lines (for example service items) | Conditional | `0` |
| data.lineResults[] | Per-line outcomes | Conditional | `[ {"lineNo":1,"productCode":"SP-100","qty":"10","status":"SUCCESS","code":"00","message":"The operation was successful"}, {"lineNo":2,"productCode":"BAD-CODE","qty":"1","status":"ERROR","code":"658","message":"goodsCode does not exist!"} ]` |

#### POST /stockout (XML sample)

- Purpose: adjust stock down for one product
- Permission: `STOCKOUT`
- Required fields:
  - `PRODUCTCODE`, `ADJUSTMENTTYPE`, `QTY`
- Optional fields:
  - `REMARKS`
- Pricing behavior:
  - Unit price is resolved from product pricing (`purchaseprice`, fallback `unitprice`) and is no longer defaulted to `1`.
- Mapping behavior:
  - `ADJUSTMENTTYPE` is normalized through stock-adjustment mappings before request dispatch and before local stock-adjustment persistence.

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally ERP 9</SYSTEMNAME>
  <PRODUCTCODE>SP-100</PRODUCTCODE>
  <ADJUSTMENTTYPE>102</ADJUSTMENTTYPE>
  <QTY>1</QTY>
  <REMARKS>Damaged item</REMARKS>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| PRODUCTCODE | Product code | Yes | `SP-100` |
| ADJUSTMENTTYPE | Stock-out reason/adjustment type | Yes | `102` |
| QTY | Quantity to deduct | Yes | `1` |
| REMARKS | Adjustment remarks | No | `Damaged item` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `99` / `659` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data | Endpoint payload container | Yes | `[]` |

#### POST /batchstockout (XML sample)

- Purpose: adjust stock down for multiple products
- Permission: `STOCKOUT`
- Required fields:
  - `VOUCHERTYPE`, `VOUCHERTYPENAME`, `VOUCHERNUMBER`, `VOUCHERREF`
  - `INVENTORIES[]` with `PRODUCTCODE`, `QTY` (and optional `RATE`)
- Optional fields:
  - `ADJUSTMENTTYPE` (defaults to `102`)
  - `REMARKS`
- Pricing behavior:
  - Line `RATE` is used when supplied; otherwise the system resolves product pricing (`purchaseprice`, fallback `unitprice`) and does not default to `1`.
- Mapping behavior:
  - `ADJUSTMENTTYPE` is normalized through stock-adjustment mappings before request dispatch and before local stock-adjustment persistence.

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally Prime</SYSTEMNAME>
  <VOUCHERTYPE>Adjustment</VOUCHERTYPE>
  <VOUCHERTYPENAME>Adjustment</VOUCHERTYPENAME>
  <VOUCHERNUMBER>ADJ-001</VOUCHERNUMBER>
  <VOUCHERREF>ERP-ADJ-001</VOUCHERREF>
  <ADJUSTMENTTYPE>102</ADJUSTMENTTYPE>
  <REMARKS>Periodic correction</REMARKS>
  <INVENTORIES>
    <INVENTORY>
      <PRODUCTCODE>SP-100</PRODUCTCODE>
      <QTY>1</QTY>
      <RATE>1000</RATE>
    </INVENTORY>
  </INVENTORIES>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| VOUCHERTYPE | Voucher type | Yes | `Adjustment` |
| VOUCHERTYPENAME | Voucher type display name | Yes | `Adjustment` |
| VOUCHERNUMBER | Voucher number | Yes | `ADJ-001` |
| VOUCHERREF | Voucher reference | Yes | `ERP-ADJ-001` |
| INVENTORIES[] | Inventory line collection | Yes | `[{"PRODUCTCODE":"SP-100","QTY":"1","RATE":"1000"}]` |
| ADJUSTMENTTYPE | Adjustment type | No | `102` |
| REMARKS | Adjustment remarks | No | `Periodic correction` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `45` / `-999` / `-998` / `99` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data.voucherNumber | Voucher number echoed for correlation | Conditional | `ADJ-001` |
| data.voucherRef | Voucher reference echoed for correlation | Conditional | `ERP-ADJ-001` |
| data.adjustmentType | Adjustment type used for request | Conditional | `102` |
| data.summary.totalLines | Total input lines received | Conditional | `3` |
| data.summary.successCount | Number of successful lines | Conditional | `1` |
| data.summary.failureCount | Number of failed lines | Conditional | `2` |
| data.lineResults[] | Per-line outcomes | Conditional | `[ {"lineNo":1,"productCode":"SP-100","qty":"1","status":"SUCCESS","code":"00","message":"The operation was successful"}, {"lineNo":2,"productCode":"BAD-CODE","qty":"1","status":"ERROR","code":"2196","message":"commodityGoodsId and goodsCode cannot be empty at the same time!"} ]` |

#### POST /stocktransfer (XML sample)

- Purpose: transfer stock between branches
- Permission: `TRANSFERPRODUCTSTOCK`
- Required fields:
  - `VOUCHERNUMBER`
  - `SOURCEINVENTORIES[]` with per-line `PRODUCTCODE`, `QTY`, `LOCATION`
  - `DESTINATIONINVENTORIES[]` with per-line `LOCATION`
- Compatibility fields:
  - `PRODUCTCODE`, `SOURCEBRANCH`, `DESTBRANCH`, `QTY` (legacy/flat naming kept for documentation continuity)
- Optional fields:
  - `REMARKS`, `STOCKITEMNAME`, `RATE`, `VOUCHERREF`, `VOUCHERTYPE`, `VOUCHERTYPENAME` (logged/persisted but not required)
- Common business errors:
  - `-999` when `VOUCHERNUMBER` is missing
- Mapping behavior:
  - Source/destination `LOCATION` values accept ERP-facing branch names/codes and are normalized to mapped branch identifiers before transfer and persistence.
  - `PRODUCTCODE` lines are resolved to mapped product code (when available) before transfer and persistence.

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally Prime</SYSTEMNAME>
  <SOURCEINVENTORIES>
    <INVENTORY>
      <STOCKITEMNAME>Sample Product</STOCKITEMNAME>
      <PRODUCTCODE>SP-100</PRODUCTCODE>
      <QTY>5</QTY>
      <RATE>1000</RATE>
      <LOCATION>Kampala HQ</LOCATION>
    </INVENTORY>
  </SOURCEINVENTORIES>
  <DESTINATIONINVENTORIES>
    <INVENTORY>
      <STOCKITEMNAME>Sample Product</STOCKITEMNAME>
      <PRODUCTCODE>SP-100</PRODUCTCODE>
      <QTY>5</QTY>
      <RATE>1000</RATE>
      <LOCATION>Jinja</LOCATION>
    </INVENTORY>
  </DESTINATIONINVENTORIES>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| SOURCEINVENTORIES[] | Source inventory collection | Yes | `[{"PRODUCTCODE":"SP-100","QTY":"5","LOCATION":"Kampala HQ"}]` |
| DESTINATIONINVENTORIES[] | Destination inventory collection | Yes | `[{"LOCATION":"Jinja"}]` |
| VOUCHERNUMBER | Voucher number used for duplicate-upload guard and stock-transfer log identity | Yes | `ST-001` |
| PRODUCTCODE | Product code (legacy/flat naming) | Conditional | `SP-100` |
| SOURCEBRANCH | Source branch code/name (legacy/flat naming) | Conditional | `Kampala HQ` |
| DESTBRANCH | Destination branch code/name (legacy/flat naming) | Conditional | `Jinja` |
| QTY | Quantity to transfer (legacy/flat naming) | Conditional | `5` |
| REMARKS | Transfer remarks (legacy/flat naming) | No | `Branch restock` |
| STOCKITEMNAME | Item name on inventory rows | No | `Sample Product` |
| RATE | Unit rate on inventory rows | No | `1000` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `99` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data | Endpoint payload container | Yes | `[]` |

#### POST /batchstocktransfer (XML sample)

- Purpose: transfer stock for multiple products between branches with line-level results
- Permission: `TRANSFERPRODUCTSTOCK`
- Required fields:
  - `VOUCHERNUMBER`
  - `SOURCEBRANCH`, `DESTBRANCH`
  - `INVENTORIES[]` with per-line `PRODUCTCODE`, `QTY`
- Optional fields:
  - `REMARKS`, `VOUCHERREF`, `VOUCHERTYPE`, `VOUCHERTYPENAME`
- Common business errors:
  - `-999` when `VOUCHERNUMBER` is missing
  - `-998` when inventory lines are missing
  - `45` when response is partial/mixed across lines
- Mapping behavior:
  - Source and destination branch values are normalized through branch mappings when available before transfer submission.
  - Product codes are resolved through product mappings when available before transfer submission and persistence.

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally Prime</SYSTEMNAME>
  <VOUCHERTYPE>Stock Journal</VOUCHERTYPE>
  <VOUCHERTYPENAME>Stock Journal</VOUCHERTYPENAME>
  <VOUCHERNUMBER>BST-001</VOUCHERNUMBER>
  <VOUCHERREF>ERP-BST-001</VOUCHERREF>
  <SOURCEBRANCH>Kampala HQ</SOURCEBRANCH>
  <DESTBRANCH>Jinja</DESTBRANCH>
  <REMARKS>Branch balancing</REMARKS>
  <INVENTORIES>
    <INVENTORY>
      <PRODUCTCODE>SP-100</PRODUCTCODE>
      <QTY>5</QTY>
    </INVENTORY>
    <INVENTORY>
      <PRODUCTCODE>SP-200</PRODUCTCODE>
      <QTY>2</QTY>
    </INVENTORY>
  </INVENTORIES>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| VOUCHERNUMBER | Voucher number used for duplicate-upload control and correlation | Yes | `BST-001` |
| SOURCEBRANCH | Source branch name/code | Yes | `Kampala HQ` |
| DESTBRANCH | Destination branch name/code | Yes | `Jinja` |
| INVENTORIES[] | Inventory line collection | Yes | `[ {"PRODUCTCODE":"SP-100","QTY":"5"}, {"PRODUCTCODE":"SP-200","QTY":"2"} ]` |
| VOUCHERREF | Voucher reference | No | `ERP-BST-001` |
| VOUCHERTYPE | Voucher type | No | `Stock Journal` |
| VOUCHERTYPENAME | Voucher type display name | No | `Stock Journal` |
| REMARKS | Transfer remarks | No | `Branch balancing` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `45` / `-999` / `-998` / `99` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data.voucherNumber | Voucher number echoed for correlation | Conditional | `BST-001` |
| data.voucherRef | Voucher reference echoed for correlation | Conditional | `ERP-BST-001` |
| data.sourceBranch | Mapped source branch identifier/name used by transfer flow | Conditional | `912550336846912433` |
| data.destBranch | Mapped destination branch identifier/name used by transfer flow | Conditional | `592478656342375774` |
| data.summary.totalLines | Total input lines received | Conditional | `3` |
| data.summary.successCount | Number of successful lines | Conditional | `1` |
| data.summary.failureCount | Number of failed lines | Conditional | `2` |
| data.lineResults[] | Per-line outcomes | Conditional | `[ {"lineNo":1,"productCode":"SP-100","qty":"1","status":"SUCCESS","code":"00","message":"The operation was successful"}, {"lineNo":2,"productCode":"BAD-CODE","qty":"1","status":"ERROR","code":"658","message":"commodityGoodsId or goodsCode does not exist!"} ]` |

### 5.2.4 Sales, credit note, debit note

#### POST /uploadinvoice (XML sample)

- Purpose: upload sales invoice
- Permission: `UPLOADINVOICE`
- Core header fields:
  - `VOUCHERTYPE`, `VOUCHERTYPENAME`, `VOUCHERNUMBER`, `VOUCHERREF`
  - Buyer profile: `BUYER...` family, `BUYERTYPE`, `BUYERCITIZENSHIP`
  - `CURRENCY`, `INDUSTRYCODE`, `PRICEVATINCLUSIVE`
- Project/terms fields:
  - `PROJECTID`, `PROJECTNAME`, `DELIVERYTERMCODE`, `NONRESIDENTFLAG`
- Line fields:
  - `INVENTORIES[]` each line usually carries:
  - `PRODUCTCODE`, `QTY`, `RATE`, `AMOUNT`, `TAXCODE`, `TAXRATE`, `DISCOUNT`, `DISCOUNTPCT`, `DISCOUNTFLAG`
- Notes:
  - Buyer TIN is validated when provided.
  - Fees mapping and excise logic may alter tax line composition.

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally Prime</SYSTEMNAME>
  <VOUCHERTYPE>Invoice</VOUCHERTYPE>
  <VOUCHERTYPENAME>Invoice</VOUCHERTYPENAME>
  <VOUCHERNUMBER>INV-SAMPLE-001</VOUCHERNUMBER>
  <VOUCHERREF>INVREF-SAMPLE-001</VOUCHERREF>
  <CURRENCY>UGX</CURRENCY>
  <INDUSTRYCODE>101</INDUSTRYCODE>
  <BUYERLEGALNAME>Sample Buyer</BUYERLEGALNAME>
  <BUYERCITIZENSHIP>UG</BUYERCITIZENSHIP>
  <BUYERTYPE>101</BUYERTYPE>
  <PRICEVATINCLUSIVE>NO</PRICEVATINCLUSIVE>
  <INVENTORIES>
    <INVENTORY>
      <PRODUCTCODE>EXC_TEST_3</PRODUCTCODE>
      <QTY>1</QTY>
      <BILLEDQTY>1</BILLEDQTY>
      <RATE>1000</RATE>
      <AMOUNT>1000</AMOUNT>
      <TAXCODE>01</TAXCODE>
      <BUOM>Pcs</BUOM>
      <COMMODITYCATEGORYCODE>101</COMMODITYCATEGORYCODE>
    </INVENTORY>
  </INVENTORIES>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| VOUCHERTYPE | Voucher type | Yes | `Invoice` |
| VOUCHERTYPENAME | Voucher type display name | Yes | `Invoice` |
| VOUCHERNUMBER | ERP voucher number | Yes | `INV-SAMPLE-001` |
| VOUCHERREF | ERP voucher reference | Yes | `INVREF-SAMPLE-001` |
| CURRENCY | Invoice currency | Yes | `UGX` |
| INDUSTRYCODE | Industry classification code | Yes | `101` |
| BUYERLEGALNAME | Buyer legal name | Yes | `Sample Buyer` |
| BUYERCITIZENSHIP | Buyer country/citizenship code | Yes | `UG` |
| BUYERTYPE | Buyer type code | Yes | `101` |
| INVENTORIES[] | Invoice line collection | Yes | `[{"PRODUCTCODE":"EXC_TEST_3","QTY":"1","RATE":"1000","AMOUNT":"1000","TAXCODE":"01"}]` |
| PRICEVATINCLUSIVE | Price includes VAT flag | No | `NO` |
| PROJECTID | Project code | No | `PRJ-001` |
| PROJECTNAME | Project name | No | `ERP Rollout` |
| DELIVERYTERMCODE | Delivery terms code | No | `FOB` |
| NONRESIDENTFLAG | Non-resident buyer flag | No | `2` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `-999` / `99` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data.INVID | EFRIS invoice id | Conditional | `2010242152271517353` |
| data.INVNO | Fiscal invoice number | Conditional | `102180132712` |
| data.ISSUEDT | Issue date/time | Conditional | `2026-04-25 10:15:00` |
| data.FDN | Fiscal device number | Conditional | `102180132712` |
| data.QRCODE | Encoded QR content | Conditional | `https://...` |

#### POST /uploadcreditnote

- Purpose: upload credit note against an original invoice
- Permission: `UPLOADCREDITNOTE`
- Required fields (in addition to invoice-like fields):
  - `ORIVOUCHERNUMBER`
  - `REASONS[]` with `REASONCODE`, `REASON`
- Typical business errors:
  - `-999` when original invoice number is missing
  - `-999` when reasons are missing
- Mapping behavior:
  - Persisted credit-note fields are normalized using mappings where available:
  - `currency` via currency mapping
  - `invoiceindustrycode` via industry mapping
  - `reasoncode` via credit/debit reason mapping

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally Prime</SYSTEMNAME>
  <VOUCHERTYPE>Credit Note</VOUCHERTYPE>
  <VOUCHERTYPENAME>Credit Note</VOUCHERTYPENAME>
  <VOUCHERNUMBER>CN-001</VOUCHERNUMBER>
  <VOUCHERREF>ERP-CN-001</VOUCHERREF>
  <ORIVOUCHERNUMBER>INV-SAMPLE-001</ORIVOUCHERNUMBER>
  <REASONS>
    <REASON>
      <REASONCODE>101</REASONCODE>
      <REASON>Return</REASON>
    </REASON>
  </REASONS>
  <INVENTORIES>
    <INVENTORY>
      <PRODUCTCODE>EXC_TEST_3</PRODUCTCODE>
      <QTY>1</QTY>
      <RATE>1000</RATE>
      <AMOUNT>1000</AMOUNT>
      <TAXCODE>01</TAXCODE>
    </INVENTORY>
  </INVENTORIES>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| VOUCHERTYPE | Voucher type | Yes | `Credit Note` |
| VOUCHERTYPENAME | Voucher type display name | Yes | `Credit Note` |
| VOUCHERNUMBER | Credit note number | Yes | `CN-001` |
| VOUCHERREF | Credit note reference | Yes | `ERP-CN-001` |
| ORIVOUCHERNUMBER | Original invoice number | Yes | `INV-SAMPLE-001` |
| REASONS[] | Credit note reason collection | Yes | `[{"REASONCODE":"101","REASON":"Return"}]` |
| INVENTORIES[] | Credit note line collection | Yes | `[{"PRODUCTCODE":"EXC_TEST_3","QTY":"1","RATE":"1000","AMOUNT":"1000","TAXCODE":"01"}]` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `-999` / `99` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data.INVID | EFRIS credit note id | Conditional | `3010242152271517353` |
| data.INVNO | Credit note number | Conditional | `CN-001` |
| data.ISSUEDT | Issue date/time | Conditional | `2026-04-25 10:20:00` |
| data.FDN | Fiscal device number | Conditional | `102180132712` |
| data.REFERENCE | ERP reference associated with the credit note | Conditional | `ERP-CN-001` |
| data.QRCODE | Encoded QR content | Conditional | `https://...` |

#### POST /uploaddebitnote

- Purpose: upload debit note against an original invoice
- Permission: `UPLOADDEBITNOTE`
- Required fields (in addition to invoice-like fields):
  - `ORIVOUCHERNUMBER`
  - `REASONS[]` with `REASONCODE`, `REASON`
- Notable behavior:
  - Maintains current discount-flag behavior.
  - Performs original invoice reconciliation before upload.

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally ERP 9</SYSTEMNAME>
  <VOUCHERTYPE>Debit Note</VOUCHERTYPE>
  <VOUCHERTYPENAME>Debit Note</VOUCHERTYPENAME>
  <VOUCHERNUMBER>DN-001</VOUCHERNUMBER>
  <VOUCHERREF>ERP-DN-001</VOUCHERREF>
  <ORIVOUCHERNUMBER>INV-SAMPLE-001</ORIVOUCHERNUMBER>
  <REASONS>
    <REASON>
      <REASONCODE>101</REASONCODE>
      <REASON>Price adjustment</REASON>
    </REASON>
  </REASONS>
  <INVENTORIES>
    <INVENTORY>
      <PRODUCTCODE>EXC_TEST_3</PRODUCTCODE>
      <QTY>1</QTY>
      <RATE>1000</RATE>
      <AMOUNT>1000</AMOUNT>
      <TAXCODE>01</TAXCODE>
    </INVENTORY>
  </INVENTORIES>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| VOUCHERTYPE | Voucher type | Yes | `Debit Note` |
| VOUCHERTYPENAME | Voucher type display name | Yes | `Debit Note` |
| VOUCHERNUMBER | Debit note number | Yes | `DN-001` |
| VOUCHERREF | Debit note reference | Yes | `ERP-DN-001` |
| ORIVOUCHERNUMBER | Original invoice number | Yes | `INV-SAMPLE-001` |
| REASONS[] | Debit note reason collection | Yes | `[{"REASONCODE":"101","REASON":"Price adjustment"}]` |
| INVENTORIES[] | Debit note line collection | Yes | `[{"PRODUCTCODE":"EXC_TEST_3","QTY":"1","RATE":"1000","AMOUNT":"1000","TAXCODE":"01"}]` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `-999` / `99` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data.INVID | EFRIS debit note id | Conditional | `4010242152271517353` |
| data.INVNO | Debit note number | Conditional | `DN-001` |
| data.ISSUEDT | Issue date/time | Conditional | `2026-04-25 10:25:00` |
| data.FDN | Fiscal device number | Conditional | `102180132712` |
| data.QRCODE | Encoded QR content | Conditional | `https://...` |

#### POST /queryinvoice (XML sample)

- Purpose: query invoice/credit note/debit note status and metadata
- Permission: `DOWNLOADINVOICE`
- Required fields:
  - `VOUCHERNUMBER`, `VOUCHERREF`, `VOUCHERTYPE`, `VOUCHERTYPENAME`
- Supported voucher type families:
  - Credit notes: `Credit Note`
  - Debit notes: `Debit Note`
  - Sales invoices: `Sales` and `Invoice` labels
- Response data fields:
  - `INVID`, `INVNO`, `ISSUEDT`, `FDN`, `QRCODE`, `REFERENCE`, `APPROVESTATUS`, `CNNUMBER`
- Unsupported voucher type behavior:
  - Returns `1006`, `Unsupported voucher type`

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally Prime</SYSTEMNAME>
  <VOUCHERNUMBER>INV-SAMPLE-001</VOUCHERNUMBER>
  <VOUCHERREF>INVREF-SAMPLE-001</VOUCHERREF>
  <VOUCHERTYPE>Invoice</VOUCHERTYPE>
  <VOUCHERTYPENAME>Invoice</VOUCHERTYPENAME>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| VOUCHERNUMBER | ERP voucher number | Yes | `INV-SAMPLE-001` |
| VOUCHERREF | ERP voucher reference | Yes | `INVREF-SAMPLE-001` |
| VOUCHERTYPE | Voucher type | Yes | `Invoice` |
| VOUCHERTYPENAME | Voucher type display name | Yes | `Invoice` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `99` / `1006` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data.INVID | EFRIS document id | Conditional | `2010242152271517353` |
| data.INVNO | Fiscal document number | Conditional | `102180132712` |
| data.ISSUEDT | Issue date/time | Conditional | `2026-04-25 10:15:00` |
| data.FDN | Fiscal device number | Conditional | `102180132712` |
| data.QRCODE | Encoded QR content | Conditional | `https://...` |
| data.REFERENCE | ERP reference | Conditional | `INVREF-SAMPLE-001` |
| data.APPROVESTATUS | Approval status | Conditional | `Approved` |
| data.CNNUMBER | Linked credit note number | Conditional | `` |

#### POST /voidcreditnote

- Purpose: void/cancel previously uploaded credit note
- Permission: `CANCELCREDITNOTE`
- Key fields:
  - `VOUCHERNUMBER`, `VOUCHERREF`, `VOUCHERTYPE`, `VOUCHERTYPENAME`
- Typical responses:
  - `00` on success
  - `99` when credit note is not found locally for the ERP id

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally ERP 9</SYSTEMNAME>
  <VOUCHERNUMBER>CN-001</VOUCHERNUMBER>
  <VOUCHERREF>ERP-CN-001</VOUCHERREF>
  <VOUCHERTYPE>Credit Note</VOUCHERTYPE>
  <VOUCHERTYPENAME>Credit Note</VOUCHERTYPENAME>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| VOUCHERNUMBER | Credit note ERP number | Yes | `CN-001` |
| VOUCHERREF | Credit note ERP reference | Yes | `ERP-CN-001` |
| VOUCHERTYPE | Voucher type | Yes | `Credit Note` |
| VOUCHERTYPENAME | Voucher type display name | Yes | `Credit Note` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `99` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data | Endpoint payload container | Yes | `[]` |

### 5.2.5 Mail

#### POST /sendmail

- Purpose: send SMTP email notification
- Permission: `SENDEMAIL`
- Required endpoint fields:
  - `RECIPIENTEMAIL`, `BODY`
- Typical responses:
  - `00` success
  - `500` missing email/body
  - `300` SMTP send failure

Sample XML payload:

```xml
<REQUEST>
  <VERSION>5.0.0</VERSION>
  <APIKEY>your-api-key</APIKEY>
  <ORGTIN>9083746521</ORGTIN>
  <ERPUSER>manager</ERPUSER>
  <WINDOWSUSER>devuser</WINDOWSUSER>
  <IPADDRESS>127.0.0.1</IPADDRESS>
  <MACADDRESS>00-00-00-00-00-00</MACADDRESS>
  <SYSTEMNAME>Tally Prime</SYSTEMNAME>
  <RECIPIENTNAME>Developer</RECIPIENTNAME>
  <RECIPIENTEMAIL>developer@example.com</RECIPIENTEMAIL>
  <SUBJECT>Test message</SUBJECT>
  <BODY>Hello from eTaxWare API</BODY>
</REQUEST>
```

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| RECIPIENTNAME | Recipient display name | No | `Developer` |
| RECIPIENTEMAIL | Recipient email address | Yes | `developer@example.com` |
| SUBJECT | Email subject | No | `Test message` |
| BODY | Email body content | Yes | `Hello from eTaxWare API` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `300` / `500` |
| response.responseMessage | API outcome message | Yes | `The operation to send an email was successful` |
| data | Endpoint payload container | Yes | `[]` |

### 5.2.6 XML Response Payload Samples (Implemented Endpoints)

The XML adapter returns business outcomes using the envelope below:

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation was successful</RETURNMESSAGE>
</RESPONSE>
```

Endpoint-specific XML response examples:

#### GET/POST /

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>It Works!</RETURNMESSAGE>
</RESPONSE>
```

#### 5.2.6 POST /testapi

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>It Works!</RETURNMESSAGE>
</RESPONSE>
```

#### 5.2.6 POST /validatetin

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation to query the taxpayer was successful</RETURNMESSAGE>
  <DATA>
    <NINBRN>/80020002851201</NINBRN>
    <LEGALNAME>FTS GROUP CONSULTING SERVICES LIMITED</LEGALNAME>
  </DATA>
</RESPONSE>
```

#### 5.2.6 POST /checktaxpayer

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation to check taxpayer was successful</RETURNMESSAGE>
  <DATA>
    <TAXPAYERTYPE>1</TAXPAYERTYPE>
    <COMMODITYTAXPAYERTYPE>2</COMMODITYTAXPAYERTYPE>
  </DATA>
</RESPONSE>
```

#### 5.2.6 POST /currencyquery

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation to fetch the currencies was successful</RETURNMESSAGE>
  <DATA>
    <UGX>1</UGX>
    <USD>3743.2163</USD>
  </DATA>
</RESPONSE>
```

#### 5.2.6 POST /uploadproduct

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation was successful</RETURNMESSAGE>
  <DATA>
    <PRODID>1419845115243627714</PRODID>
    <STATUS>Active</STATUS>
  </DATA>
</RESPONSE>
```

#### 5.2.6 POST /fetchproduct

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation was successful</RETURNMESSAGE>
  <DATA>
    <PRODUCTCODE>SP-100</PRODUCTCODE>
    <ITEMNAME>Sample Product</ITEMNAME>
  </DATA>
</RESPONSE>
```

#### 5.2.6 POST /stockin

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation was successful</RETURNMESSAGE>
</RESPONSE>
```

#### 5.2.6 POST /batchstockin

```xml
<RESPONSE>
  <RETURNCODE>45</RETURNCODE>
  <RETURNMESSAGE>Partial Error. Contact your system administrator!</RETURNMESSAGE>
  <DATA>
    <SUMMARY><TOTALLINES>3</TOTALLINES><SUCCESSCOUNT>1</SUCCESSCOUNT><FAILURECOUNT>2</FAILURECOUNT><SKIPPEDCOUNT>0</SKIPPEDCOUNT></SUMMARY>
  </DATA>
</RESPONSE>
```

#### 5.2.6 POST /stockout

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation was successful</RETURNMESSAGE>
</RESPONSE>
```

#### 5.2.6 POST /batchstockout

```xml
<RESPONSE>
  <RETURNCODE>45</RETURNCODE>
  <RETURNMESSAGE>Partial Error. Contact your system administrator!</RETURNMESSAGE>
  <DATA>
    <SUMMARY><TOTALLINES>3</TOTALLINES><SUCCESSCOUNT>1</SUCCESSCOUNT><FAILURECOUNT>2</FAILURECOUNT></SUMMARY>
  </DATA>
</RESPONSE>
```

#### 5.2.6 POST /stocktransfer

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation was successful</RETURNMESSAGE>
</RESPONSE>
```

#### 5.2.6 POST /batchstocktransfer

```xml
<RESPONSE>
  <RETURNCODE>45</RETURNCODE>
  <RETURNMESSAGE>Partial Error. Contact your system administrator!</RETURNMESSAGE>
  <DATA>
    <SUMMARY><TOTALLINES>3</TOTALLINES><SUCCESSCOUNT>1</SUCCESSCOUNT><FAILURECOUNT>2</FAILURECOUNT></SUMMARY>
  </DATA>
</RESPONSE>
```

#### 5.2.6 POST /uploadinvoice

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation was successful</RETURNMESSAGE>
  <DATA>
    <INVID>2010242152271517353</INVID>
    <INVNO>102180132712</INVNO>
  </DATA>
</RESPONSE>
```

#### 5.2.6 POST /queryinvoice

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation was successful</RETURNMESSAGE>
  <DATA>
    <INVID>2010242152271517353</INVID>
    <APPROVESTATUS>Approved</APPROVESTATUS>
  </DATA>
</RESPONSE>
```

#### 5.2.6 POST /uploadcreditnote

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation was successful</RETURNMESSAGE>
  <DATA>
    <INVID>3010242152271517353</INVID>
    <INVNO>CN-SAMPLE-001</INVNO>
  </DATA>
</RESPONSE>
```

#### 5.2.6 POST /voidcreditnote

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation was successful</RETURNMESSAGE>
</RESPONSE>
```

#### 5.2.6 POST /uploaddebitnote

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation was successful</RETURNMESSAGE>
  <DATA>
    <INVID>4010242152271517353</INVID>
    <INVNO>DN-SAMPLE-001</INVNO>
  </DATA>
</RESPONSE>
```

#### 5.2.6 POST /sendmail

```xml
<RESPONSE>
  <RETURNCODE>00</RETURNCODE>
  <RETURNMESSAGE>The operation to send an email was successful</RETURNMESSAGE>
</RESPONSE>
```

## 6. Not Implemented in Current Class Set

The following routes are declared but have no matching method in active class files and currently return method/routing errors at runtime:

- `/uploadcommoditycode`
- `/printinvoice`
- `/querycreditnote`
- `/querydebitnote`
- `/loadrateunits`
- `/uploadcustomer`
- `/uploadsupplier`
- `/uploadimportedservice`

## 7. Minimal Payload Template

Use this base object for all POST requests, then append endpoint-specific fields.

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "<your-api-key>",
  "ORGTIN": "<integrated-company-tin>",
  "ERPUSER": "<erp-user-code>",
  "WINDOWSUSER": "<windows-user>",
  "IPADDRESS": "<client-ip>",
  "MACADDRESS": "<mac-address>",
  "SYSTEMNAME": "<host-name>"
}
```

## 8. Testing Recommendations

- Always test with valid `ORGTIN`, `APIKEY`, and `VERSION` first; otherwise pre-route rejection masks endpoint behavior.
- Start with health and lookup endpoints:
  - `/testapi`, `/validatetin`, `/currencyquery`
- For transactional routes, test with both:
  - Minimal valid payload
  - Intentional negative payload to verify deterministic business errors
- Track both HTTP status and envelope code/message. Most business failures return HTTP 200 with non-`00` response codes.

## 9. Change Log (Guide)

- 2026-04-25: Documented v12 mapping hardening for stock-in, batch stock-in, stock-out, batch stock-out, stock transfer, and credit-note persistence normalization.
- 2026-04-25: Initial extensive endpoint guide for the active adapter, including implemented/unimplemented route status and payload conventions.

## 10. Sample Request and Response Payloads

These examples are intentionally practical and aligned to current runtime behavior.

### 10.1 Common request base (reuse in all examples)

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local"
}
```

### 10.2 POST /testapi

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/testapi" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local"
  }'
```

Sample response:

```json
{
  "response": {
    "responseCode": "00",
    "responseMessage": "It Works!"
  },
  "data": []
}
```

### 10.3 POST /validatetin

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local",
  "TIN": "9083746521"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/validatetin" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local",
    "TIN":"9083746521"
  }'
```

Sample success response:

```json
{
  "response": {
    "responseCode": "00",
    "responseMessage": "The operation to query the taxpayer was successful"
  },
  "data": {
    "NINBRN": "/80020002851201",
    "LEGALNAME": "FTS GROUP CONSULTING SERVICES LIMITED",
    "BUSINESSNAME": "FTS GROUP CONSULTING SERVICES LIMITED",
    "CONTACTNUMBER": "+256414123456",
    "CONTACTEMAIL": "taxpayer@example.com",
    "ADDRESS": "Plot 10 Kampala Road, Kampala"
  }
}
```

Sample validation failure response (missing TIN):

```json
{
  "response": {
    "responseCode": "-999",
    "responseMessage": "No TIN was supplied"
  },
  "data": {
    "NINBRN": "",
    "LEGALNAME": "",
    "BUSINESSNAME": "",
    "CONTACTNUMBER": "",
    "CONTACTEMAIL": "",
    "ADDRESS": ""
  }
}
```

### 10.4 POST /currencyquery

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/currencyquery" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local"
  }'
```

Sample response:

```json
{
  "response": {
    "responseCode": "00",
    "responseMessage": "The operation to fetch the currencies was successful"
  },
  "data": {
    "UGX": "1",
    "USD": "3743.2163",
    "EUR": "4329.3637"
  }
}
```

### 10.5 POST /queryinvoice

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local",
  "VOUCHERNUMBER": "INV-ERP-SAMPLE",
  "VOUCHERREF": "INV-ERP-NO-SAMPLE",
  "VOUCHERTYPE": "Invoice",
  "VOUCHERTYPENAME": "Invoice"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/queryinvoice" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local",
    "VOUCHERNUMBER":"INV-ERP-SAMPLE",
    "VOUCHERREF":"INV-ERP-NO-SAMPLE",
    "VOUCHERTYPE":"Invoice",
    "VOUCHERTYPENAME":"Invoice"
  }'
```

Sample response when ERP invoice id is not found locally:

```json
{
  "response": {
    "responseCode": "99",
    "responseMessage": "The invoice does not exist on EFRIS"
  },
  "data": {
    "INVID": "",
    "INVNO": "",
    "ISSUEDT": "",
    "FDN": "",
    "QRCODE": "",
    "REFERENCE": "",
    "APPROVESTATUS": "",
    "CNNUMBER": ""
  }
}
```

Sample response for unsupported voucher type:

```json
{
  "response": {
    "responseCode": "1006",
    "responseMessage": "Unsupported voucher type"
  },
  "data": {
    "INVID": "",
    "INVNO": "",
    "ISSUEDT": "",
    "FDN": "",
    "QRCODE": "",
    "REFERENCE": "",
    "APPROVESTATUS": "",
    "CNNUMBER": ""
  }
}
```

### 10.6 POST /uploadproduct

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local",
  "ITEMNAME": "Sample Product",
  "ITEMID": "SP-100",
  "MEASUREUNITS": "Pcs",
  "COMMODITYCODE": "101",
  "PRODUCTCODE": "SP-100",
  "CURRENCY": "UGX",
  "HASEXCISEDUTYFLAG": "No",
  "EXCISEDUTYCODE": "",
  "HAVEPIECEUNITSFLAG": "No",
  "PIECEUNITSMEASUREUNIT": "Pcs",
  "PIECEUNITPRICE": "0",
  "PACKAGESCALEVALUE": "0",
  "PIECESCALEVALUE": "0",
  "STOCKPREWARNING": "0",
  "UNITPRICE": "1000",
  "TAXCODE": "01",
  "HSCODE": "101",
  "CUSTOMMEASUREUNIT": "",
  "CUSTOMUNITPRICE": "0",
  "CUSTOMPACKAGESCALEDVALUE": "0",
  "CUSTOMSCALEDVALUE": "0",
  "CUSTOMWEIGHT": "0"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/uploadproduct" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local",
    "ITEMNAME":"Sample Product",
    "ITEMID":"SP-100",
    "MEASUREUNITS":"Pcs",
    "COMMODITYCODE":"101",
    "PRODUCTCODE":"SP-100",
    "CURRENCY":"UGX",
    "HASEXCISEDUTYFLAG":"No",
    "EXCISEDUTYCODE":"",
    "HAVEPIECEUNITSFLAG":"No",
    "PIECEUNITSMEASUREUNIT":"Pcs",
    "PIECEUNITPRICE":"0",
    "PACKAGESCALEVALUE":"0",
    "PIECESCALEVALUE":"0",
    "STOCKPREWARNING":"0",
    "UNITPRICE":"1000",
    "TAXCODE":"01",
    "HSCODE":"101",
    "CUSTOMMEASUREUNIT":"",
    "CUSTOMUNITPRICE":"0",
    "CUSTOMPACKAGESCALEDVALUE":"0",
    "CUSTOMSCALEDVALUE":"0",
    "CUSTOMWEIGHT":"0"
  }'
```

Sample validation failure response (missing TAXCODE):

```json
{
  "response": {
    "responseCode": "2398",
    "responseMessage": "The PRODUCTCODE SP-100 does not have a TAXCODE!"
  },
  "data": {
    "ISTAXEXEMPT": "",
    "ISZERORATED": "",
    "TAXRATE": "",
    "STATUS": "",
    "SOURCE": "",
    "EXCLUSION": "",
    "PRODID": "",
    "SERVICEMARK": ""
  }
}
```

### 10.7 POST /uploadinvoice

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local",
  "BUYERADDRESS": "Kampala",
  "BUYEREMAIL": "buyer@example.com",
  "BUYERCITIZENSHIP": "UG",
  "BUYERLEGALNAME": "Sample Buyer",
  "MOBILEPHONE": "+256700123456",
  "CURRENCY": "UGX",
  "INDUSTRYCODE": "101",
  "BUYERSECTOR": "Retail",
  "BUYERNINBRN": "CF12345678",
  "BUYERTIN": "1012345678",
  "VOUCHERNUMBER": "INV-SAMPLE-001",
  "PROJECTID": "PRJ-001",
  "BRANCH": "Kampala HQ",
  "VOUCHERTYPE": "Invoice",
  "BUYERREFERENCENO": "PO-7787",
  "BUSINESSNAME": "Sample Buyer",
  "VOUCHERTYPENAME": "Invoice",
  "INVENTORIES": [
    {
      "RATE": "1000",
      "DISCOUNTPCT": "0",
      "PRODUCTCODE": "EXC_TEST_3",
      "TAXRATE": "0.18",
      "DISCOUNT": "0",
      "TAXCODE": "01",
      "TOTALWEIGHT": "0",
      "QTY": "1",
      "DISCOUNTFLAG": "2",
      "AMOUNT": "1000"
    }
  ],
  "PROJECTNAME": "Tax Integration Pilot",
  "PRICEVATINCLUSIVE": "NO",
  "BUYERPASSPORTNUM": "B1234567",
  "VOUCHERREF": "INVREF-SAMPLE-001",
  "BUYERPLACEOFBUSI": "Kampala Uganda",
  "BUYERTYPE": "101",
  "NONRESIDENTFLAG": "2",
  "BUYERLINEPHONE": "+256414123456",
  "DELIVERYTERMCODE": "FOB",
  "REMARKS": "sample"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/uploadinvoice" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local",
    "BUYERADDRESS":"Kampala",
    "BUYEREMAIL":"buyer@example.com",
    "BUYERCITIZENSHIP":"UG",
    "BUYERLEGALNAME":"Sample Buyer",
    "MOBILEPHONE":"+256700123456",
    "CURRENCY":"UGX",
    "INDUSTRYCODE":"101",
    "BUYERSECTOR":"Retail",
    "BUYERNINBRN":"CF12345678",
    "BUYERTIN":"1012345678",
    "VOUCHERNUMBER":"INV-SAMPLE-001",
    "PROJECTID":"PRJ-001",
    "BRANCH":"Kampala HQ",
    "VOUCHERTYPE":"Invoice",
    "BUYERREFERENCENO":"PO-7787",
    "BUSINESSNAME":"Sample Buyer",
    "VOUCHERTYPENAME":"Invoice",
    "INVENTORIES":[{"RATE":"1000","DISCOUNTPCT":"0","PRODUCTCODE":"EXC_TEST_3","TAXRATE":"0.18","DISCOUNT":"0","TAXCODE":"01","TOTALWEIGHT":"0","QTY":"1","DISCOUNTFLAG":"2","AMOUNT":"1000"}],
    "PROJECTNAME":"Tax Integration Pilot",
    "PRICEVATINCLUSIVE":"NO",
    "BUYERPASSPORTNUM":"B1234567",
    "VOUCHERREF":"INVREF-SAMPLE-001",
    "BUYERPLACEOFBUSI":"Kampala Uganda",
    "BUYERTYPE":"101",
    "NONRESIDENTFLAG":"2",
    "BUYERLINEPHONE":"+256414123456",
    "DELIVERYTERMCODE":"FOB",
    "REMARKS":"sample"
  }'
```

Sample business validation response:

```json
{
  "response": {
    "responseCode": "-999",
    "responseMessage": "The TAXCODE on PRODUCTCODE EXC_TEST_3 is not defined!"
  },
  "data": {
    "INVID": "",
    "INVNO": "",
    "ISSUEDT": "",
    "FDN": "",
    "REFERENCE": "",
    "QRCODE": ""
  }
}
```

### 10.8 POST /sendmail

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local",
  "RECIPIENTNAME": "Developer",
  "RECIPIENTEMAIL": "developer@example.com",
  "SUBJECT": "Test message",
  "BODY": "Hello from eTaxWare API"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/sendmail" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local",
    "RECIPIENTNAME":"Developer",
    "RECIPIENTEMAIL":"developer@example.com",
    "SUBJECT":"Test message",
    "BODY":"Hello from eTaxWare API"
  }'
```

Sample SMTP failure response:

```json
{
  "response": {
    "responseCode": "300",
    "responseMessage": "The operation to send an email was not successful"
  },
  "data": []
}
```

### 10.9 POST /uploadcreditnote

Sample request:

```json
{
  "VOUCHERNUMBER": "BATCN008",
  "CUSTOMERACCOUNT": "CUST-0001",
  "BUYERTIN": "1012345678",
  "CURRENCY": "USD",
  "VOUCHERTYPE": "Credit Note",
  "VOUCHERTYPENAME": "Credit Note",
  "VOUCHERREF": "ERP-CN-REF-008",
  "ORIVOUCHERNUMBER": "C783",
  "BUYERSECTOR": "Retail",
  "BUYERTYPE": "B2C",
  "INDUSTRYCODE": "101",
  "BUYERNINBRN": "CF12345678",
  "BUYERADDRESS": "Plot 10 Kampala Road, Kampala",
  "BUYERCITIZENSHIP": "UG",
  "BUYEREMAIL": "customer@example.com",
  "MOBILEPHONE": "+256700123456",
  "BUYERLINEPHONE": "+256414123456",
  "BUYERPLACEOFBUSI": "Kampala",
  "BUYERREFERENCENO": "PO-7788",
  "BUYERPASSPORTNUM": "B1234567",
  "BUYERLEGALNAME": "Customer",
  "BUSINESSNAME": "Customer Business Ltd",
  "PRICEVATINCLUSIVE": "NO",
  "PROJECTID": "PRJ-001",
  "PROJECTNAME": "Tax Integration Pilot",
  "DELIVERYTERMSCODE": "FOB",
  "NONRESIDENTFLAG": "2",
  "DEEMEDFLAG": "2",
  "VERSION": "5.0.0",
  "ERPUSER": "manager",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "WINDOWSUSER": "svc_etax",
  "IPADDRESS": "192.168.1.20",
  "MACADDRESS": "00-1A-2B-3C-4D-5E",
  "SYSTEMNAME": "ERP-SERVER-01",
  "REASONS": [
    {
      "REASONCODE": "101",
      "REASON": "Buyer Returned"
    }
  ],
  "BRANCH": "Kampala HQ",
  "INVENTORIES": [
    {
      "PRODUCTCODE": "ALTQTYSAMPLE002",
      "QTY": "2",
      "MEASUREUNITS": "Pcs",
      "AMOUNT": "726392.25",
      "DISCOUNT": "0",
      "DISCOUNTPCT": "0",
      "RATE": "726392.25",
      "TAX": "130750.61",
      "TAXRATE": "0.18",
      "TAXCODE": "A2"
    }
  ]
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/uploadcreditnote" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VOUCHERNUMBER":"BATCN008",
    "CUSTOMERACCOUNT":"CUST-0001",
    "BUYERTIN":"1012345678",
    "CURRENCY":"USD",
    "VOUCHERTYPE":"Credit Note",
    "VOUCHERTYPENAME":"Credit Note",
    "VOUCHERREF":"ERP-CN-REF-008",
    "ORIVOUCHERNUMBER":"C783",
    "BUYERSECTOR":"Retail",
    "BUYERTYPE":"B2C",
    "INDUSTRYCODE":"101",
    "BUYERNINBRN":"CF12345678",
    "BUYERADDRESS":"Plot 10 Kampala Road, Kampala",
    "BUYERCITIZENSHIP":"UG",
    "BUYEREMAIL":"customer@example.com",
    "MOBILEPHONE":"+256700123456",
    "BUYERLINEPHONE":"+256414123456",
    "BUYERPLACEOFBUSI":"Kampala",
    "BUYERREFERENCENO":"PO-7788",
    "BUYERPASSPORTNUM":"B1234567",
    "BUYERLEGALNAME":"Customer",
    "BUSINESSNAME":"Customer Business Ltd",
    "PRICEVATINCLUSIVE":"NO",
    "PROJECTID":"PRJ-001",
    "PROJECTNAME":"Tax Integration Pilot",
    "DELIVERYTERMSCODE":"FOB",
    "NONRESIDENTFLAG":"2",
    "DEEMEDFLAG":"2",
    "VERSION":"5.0.0",
    "ERPUSER":"manager",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "WINDOWSUSER":"svc_etax",
    "IPADDRESS":"192.168.1.20",
    "MACADDRESS":"00-1A-2B-3C-4D-5E",
    "SYSTEMNAME":"ERP-SERVER-01",
    "REASONS":[{"REASONCODE":"101","REASON":"Buyer Returned"}],
    "BRANCH":"Kampala HQ",
    "INVENTORIES":[{"PRODUCTCODE":"ALTQTYSAMPLE002","QTY":"2","MEASUREUNITS":"Pcs","AMOUNT":"726392.25","DISCOUNT":"0","DISCOUNTPCT":"0","RATE":"726392.25","TAX":"130750.61","TAXRATE":"0.18","TAXCODE":"A2"}]
  }'
```

Sample business validation response:

```json
{
  "response": {
    "responseCode": "-999",
    "responseMessage": "No original invoice number was supplied"
  },
  "data": {
    "INVID": "",
    "INVNO": "",
    "ISSUEDT": "",
    "FDN": "",
    "QRCODE": ""
  }
}
```

Sample success response:

```json
{
  "response": {
    "responseCode": "00",
    "responseMessage": "The operation was successful"
  },
  "data": {
    "INVID": "3010242152271517353",
    "INVNO": "CN-SAMPLE-001",
    "ISSUEDT": "2026-05-07 11:20:00",
    "FDN": "102180132712",
    "REFERENCE": "ERP-CN-001",
    "QRCODE": "https://efris.ura.go.ug/qr/credit-note-sample"
  }
}
```

### 10.10 POST /uploaddebitnote

Sample request:

```json
{
  "VOUCHERNUMBER": "BATINV008",
  "CUSTOMERACCOUNT": "CUST-0002",
  "BUYERTIN": "1012345678",
  "CURRENCY": "USD",
  "VOUCHERTYPE": "Debit Note",
  "VOUCHERTYPENAME": "Debit Note",
  "VOUCHERREF": "ERP-DN-REF-008",
  "ORIVOUCHERNUMBER": "C784",
  "BUYERSECTOR": "Retail",
  "BUYERTYPE": "B2C",
  "INDUSTRYCODE": "101",
  "BUYERNINBRN": "CF12345678",
  "BUYERADDRESS": "Plot 10 Kampala Road, Kampala",
  "BUYERCITIZENSHIP": "UG",
  "BUYEREMAIL": "customer@example.com",
  "MOBILEPHONE": "+256700123456",
  "BUYERLINEPHONE": "+256414123456",
  "BUYERPLACEOFBUSI": "Kampala",
  "BUYERREFERENCEN": "PO-7789",
  "BUYERPASSPORTNUM": "B1234567",
  "BUYERLEGALNAME": "Customer",
  "BUSINESSNAME": "Customer Business Ltd",
  "PRICEVATINCLUSIVE": "NO",
  "PROJECTID": "PRJ-001",
  "PROJECTNAME": "Tax Integration Pilot",
  "DELIVERYTERMSCODE": "FOB",
  "NONRESIDENTFLAG": "2",
  "DEEMEDFLAG": "2",
  "VERSION": "5.0.0",
  "ERPUSER": "manager",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "WINDOWSUSER": "svc_etax",
  "IPADDRESS": "192.168.1.20",
  "MACADDRESS": "00-1A-2B-3C-4D-5E",
  "SYSTEMNAME": "ERP-SERVER-01",
  "REASONS": [
    {
      "REASONCODE": "101",
      "REASON": "Buyer Refused"
    }
  ],
  "BRANCH": "Kampala HQ",
  "INVENTORIES": [
    {
      "PRODUCTCODE": "ALTQTYSAMPLE002",
      "QTY": "2",
      "MEASUREUNITS": "Pcs",
      "AMOUNT": "726392.25",
      "DISCOUNT": "0",
      "DISCOUNTPCT": "0",
      "RATE": "726392.25",
      "TAX": "130750.61",
      "TAXRATE": "0.18",
      "TAXCODE": "A2"
    }
  ]
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/uploaddebitnote" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VOUCHERNUMBER":"BATINV008",
    "CUSTOMERACCOUNT":"CUST-0002",
    "BUYERTIN":"1012345678",
    "CURRENCY":"USD",
    "VOUCHERTYPE":"Debit Note",
    "VOUCHERTYPENAME":"Debit Note",
    "VOUCHERREF":"ERP-DN-REF-008",
    "ORIVOUCHERNUMBER":"C784",
    "BUYERSECTOR":"Retail",
    "BUYERTYPE":"B2C",
    "INDUSTRYCODE":"101",
    "BUYERNINBRN":"CF12345678",
    "BUYERADDRESS":"Plot 10 Kampala Road, Kampala",
    "BUYERCITIZENSHIP":"UG",
    "BUYEREMAIL":"customer@example.com",
    "MOBILEPHONE":"+256700123456",
    "BUYERLINEPHONE":"+256414123456",
    "BUYERPLACEOFBUSI":"Kampala",
    "BUYERREFERENCEN":"PO-7789",
    "BUYERPASSPORTNUM":"B1234567",
    "BUYERLEGALNAME":"Customer",
    "BUSINESSNAME":"Customer Business Ltd",
    "PRICEVATINCLUSIVE":"NO",
    "PROJECTID":"PRJ-001",
    "PROJECTNAME":"Tax Integration Pilot",
    "DELIVERYTERMSCODE":"FOB",
    "NONRESIDENTFLAG":"2",
    "DEEMEDFLAG":"2",
    "VERSION":"5.0.0",
    "ERPUSER":"manager",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "WINDOWSUSER":"svc_etax",
    "IPADDRESS":"192.168.1.20",
    "MACADDRESS":"00-1A-2B-3C-4D-5E",
    "SYSTEMNAME":"ERP-SERVER-01",
    "REASONS":[{"REASONCODE":"101","REASON":"Buyer Refused"}],
    "BRANCH":"Kampala HQ",
    "INVENTORIES":[{"PRODUCTCODE":"ALTQTYSAMPLE002","QTY":"2","MEASUREUNITS":"Pcs","AMOUNT":"726392.25","DISCOUNT":"0","DISCOUNTPCT":"0","RATE":"726392.25","TAX":"130750.61","TAXRATE":"0.18","TAXCODE":"A2"}]
  }'
```

Sample business validation response:

```json
{
  "response": {
    "responseCode": "-999",
    "responseMessage": "No original invoice number was supplied"
  },
  "data": {
    "INVID": "",
    "INVNO": "",
    "ISSUEDT": "",
    "FDN": "",
    "QRCODE": ""
  }
}
```

Sample success response:

```json
{
  "response": {
    "responseCode": "00",
    "responseMessage": "The operation was successful"
  },
  "data": {
    "INVID": "4010242152271517353",
    "INVNO": "DN-SAMPLE-001",
    "ISSUEDT": "2026-05-07 11:25:00",
    "FDN": "102180132712",
    "QRCODE": "https://efris.ura.go.ug/qr/debit-note-sample"
  }
}
```

### 10.11 POST /fetchproduct

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local",
  "PRODUCTCODE": "SP-100",
  "ERPQTY": "0"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/fetchproduct" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local",
    "PRODUCTCODE":"SP-100",
    "ERPQTY":"0"
  }'
```

Sample response:

```json
{
  "response": {
    "responseCode": "00",
    "responseMessage": "The operation was successful"
  },
  "data": {
    "PRODUCTCODE": "SP-100",
    "ITEMNAME": "Sample Product",
    "TAXRATE": "0.18",
    "STATUS": "Active",
    "SERVICEMARK": "102"
  }
}
```

### 10.12 POST /stockin

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local",
  "PRODUCTCODE": "SP-100",
  "STOCKINTYPE": "101",
  "UNITPRICE": "1000",
  "QTY": "10",
  "SUPPLIERTIN": "",
  "SUPPLIERNAME": ""
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/stockin" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local",
    "PRODUCTCODE":"SP-100",
    "STOCKINTYPE":"101",
    "UNITPRICE":"1000",
    "QTY":"10",
    "SUPPLIERTIN":"",
    "SUPPLIERNAME":""
  }'
```

Sample response:

```json
{
  "response": {
    "responseCode": "00",
    "responseMessage": "The operation was successful"
  },
  "data": []
}
```

### 10.13 POST /stockout

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local",
  "PRODUCTCODE": "SP-100",
  "ADJUSTMENTTYPE": "102",
  "QTY": "1",
  "REMARKS": "Damaged item"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/stockout" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local",
    "PRODUCTCODE":"SP-100",
    "ADJUSTMENTTYPE":"102",
    "QTY":"1",
    "REMARKS":"Damaged item"
  }'
```

Sample response:

```json
{
  "response": {
    "responseCode": "00",
    "responseMessage": "The operation was successful"
  },
  "data": []
}
```

### 10.14 POST /stocktransfer

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "svc-etw",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "ETW-LOCAL",
  "VOUCHERTYPE": "Stock Journal",
  "VOUCHERTYPENAME": "Stock Journal",
  "VOUCHERNUMBER": "ST-SAMPLE-001",
  "VOUCHERREF": "ST-SAMPLE-001",
  "PRODUCTCODE": "19",
  "SOURCEBRANCH": "Kampala HQ",
  "DESTBRANCH": "Jinja",
  "QTY": "1",
  "REMARKS": "Branch restock"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/stocktransfer" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"svc-etw",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"ETW-LOCAL",
    "VOUCHERTYPE":"Stock Journal",
    "VOUCHERTYPENAME":"Stock Journal",
    "VOUCHERNUMBER":"ST-SAMPLE-001",
    "VOUCHERREF":"ST-SAMPLE-001",
    "PRODUCTCODE":"19",
    "SOURCEBRANCH":"Kampala HQ",
    "DESTBRANCH":"Jinja",
    "QTY":"1",
    "REMARKS":"Branch restock"
  }'
```

Sample response:

```json
{
  "response": {
    "responseCode": "00",
    "responseMessage": "The operation was successful"
  },
  "data": []
}
```

### 10.15 POST /batchstockin

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "svc-etw",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "ETW-LOCAL",
  "VOUCHERTYPE": "Stock Journal",
  "VOUCHERTYPENAME": "Stock Journal",
  "VOUCHERNUMBER": "BIN-SAMPLE-001",
  "VOUCHERREF": "BIN-SAMPLE-001",
  "STOCKINTYPE": "102",
  "SUPPLIERTIN": "",
  "SUPPLIERNAME": "",
  "INVENTORIES": [
    {
      "PRODUCTCODE": "19",
      "QTY": "1",
      "RATE": "1000"
    },
    {
      "PRODUCTCODE": "RND-PROD-AAA",
      "QTY": "1",
      "RATE": "1000"
    },
    {
      "PRODUCTCODE": "RND-PROD-BBB",
      "QTY": "1",
      "RATE": "1000"
    }
  ]
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/batchstockin" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"svc-etw",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"ETW-LOCAL",
    "VOUCHERTYPE":"Stock Journal",
    "VOUCHERTYPENAME":"Stock Journal",
    "VOUCHERNUMBER":"BIN-SAMPLE-001",
    "VOUCHERREF":"BIN-SAMPLE-001",
    "STOCKINTYPE":"102",
    "SUPPLIERTIN":"",
    "SUPPLIERNAME":"",
    "INVENTORIES":[
      {"PRODUCTCODE":"19","QTY":"1","RATE":"1000"},
      {"PRODUCTCODE":"RND-PROD-AAA","QTY":"1","RATE":"1000"},
      {"PRODUCTCODE":"RND-PROD-BBB","QTY":"1","RATE":"1000"}
    ]
  }'
```

Sample response (all lines failed):

```json
{
  "response": {
    "responseCode": "99",
    "responseMessage": "goodsCode does not exist!"
  },
  "data": {
    "voucherNumber": "BIN-SAMPLE-001",
    "voucherRef": "BIN-SAMPLE-001",
    "stockInType": "102",
    "summary": {
      "totalLines": 3,
      "successCount": 0,
      "failureCount": 3,
      "skippedCount": 0
    },
    "lineResults": [
      {
        "lineNo": 1,
        "productCode": "19",
        "qty": "1",
        "status": "ERROR",
        "code": "658",
        "message": "goodsCode does not exist!"
      },
      {
        "lineNo": 2,
        "productCode": "RND-PROD-AAA",
        "qty": "1",
        "status": "ERROR",
        "code": "658",
        "message": "goodsCode does not exist!"
      },
      {
        "lineNo": 3,
        "productCode": "RND-PROD-BBB",
        "qty": "1",
        "status": "ERROR",
        "code": "658",
        "message": "goodsCode does not exist!"
      }
    ]
  }
}
```

### 10.16 POST /batchstockout

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "svc-etw",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "ETW-LOCAL",
  "VOUCHERTYPE": "Stock Journal",
  "VOUCHERTYPENAME": "Stock Journal",
  "VOUCHERNUMBER": "BOUT-SAMPLE-001",
  "VOUCHERREF": "BOUT-SAMPLE-001",
  "ADJUSTMENTTYPE": "102",
  "REMARKS": "Batch stockout mixed-line sample",
  "INVENTORIES": [
    {
      "PRODUCTCODE": "19",
      "QTY": "1",
      "RATE": "1000"
    },
    {
      "PRODUCTCODE": "RND-PROD-CCC",
      "QTY": "1",
      "RATE": "1000"
    },
    {
      "PRODUCTCODE": "RND-PROD-DDD",
      "QTY": "1",
      "RATE": "1000"
    }
  ]
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/batchstockout" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"svc-etw",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"ETW-LOCAL",
    "VOUCHERTYPE":"Stock Journal",
    "VOUCHERTYPENAME":"Stock Journal",
    "VOUCHERNUMBER":"BOUT-SAMPLE-001",
    "VOUCHERREF":"BOUT-SAMPLE-001",
    "ADJUSTMENTTYPE":"102",
    "REMARKS":"Batch stockout mixed-line sample",
    "INVENTORIES":[
      {"PRODUCTCODE":"19","QTY":"1","RATE":"1000"},
      {"PRODUCTCODE":"RND-PROD-CCC","QTY":"1","RATE":"1000"},
      {"PRODUCTCODE":"RND-PROD-DDD","QTY":"1","RATE":"1000"}
    ]
  }'
```

Sample response (partial/mixed):

```json
{
  "response": {
    "responseCode": "45",
    "responseMessage": "Partial Error. Contact your system administrator! 19: commodityGoodsId and goodsCode cannot be empty at the same time!; RND-PROD-CCC: commodityGoodsId and goodsCode cannot be empty at the same time!"
  },
  "data": {
    "voucherNumber": "BOUT-SAMPLE-001",
    "voucherRef": "BOUT-SAMPLE-001",
    "adjustmentType": "102",
    "summary": {
      "totalLines": 3,
      "successCount": 1,
      "failureCount": 2
    },
    "lineResults": [
      {
        "lineNo": 1,
        "productCode": "19",
        "qty": "1",
        "status": "ERROR",
        "code": "2196",
        "message": "commodityGoodsId and goodsCode cannot be empty at the same time!"
      },
      {
        "lineNo": 2,
        "productCode": "RND-PROD-CCC",
        "qty": "1",
        "status": "ERROR",
        "code": "2196",
        "message": "commodityGoodsId and goodsCode cannot be empty at the same time!"
      },
      {
        "lineNo": 3,
        "productCode": "RND-PROD-DDD",
        "qty": "1",
        "status": "SUCCESS",
        "code": "00",
        "message": "The operation was successful"
      }
    ]
  }
}
```

### 10.17 POST /batchstocktransfer

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "svc-etw",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "ETW-LOCAL",
  "VOUCHERTYPE": "Stock Journal",
  "VOUCHERTYPENAME": "Stock Journal",
  "VOUCHERNUMBER": "BT-SAMPLE-001",
  "VOUCHERREF": "BT-SAMPLE-001",
  "SOURCEBRANCH": "Kampala HQ",
  "DESTBRANCH": "Jinja",
  "REMARKS": "Batch transfer mixed-line sample",
  "INVENTORIES": [
    {
      "PRODUCTCODE": "19",
      "QTY": "1"
    },
    {
      "PRODUCTCODE": "RND-PROD-X1",
      "QTY": "1"
    },
    {
      "PRODUCTCODE": "RND-PROD-X2",
      "QTY": "1"
    }
  ]
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/batchstocktransfer" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"svc-etw",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"ETW-LOCAL",
    "VOUCHERTYPE":"Stock Journal",
    "VOUCHERTYPENAME":"Stock Journal",
    "VOUCHERNUMBER":"BT-SAMPLE-001",
    "VOUCHERREF":"BT-SAMPLE-001",
    "SOURCEBRANCH":"Kampala HQ",
    "DESTBRANCH":"Jinja",
    "REMARKS":"Batch transfer mixed-line sample",
    "INVENTORIES":[
      {"PRODUCTCODE":"19","QTY":"1"},
      {"PRODUCTCODE":"RND-PROD-X1","QTY":"1"},
      {"PRODUCTCODE":"RND-PROD-X2","QTY":"1"}
    ]
  }'
```

Sample response (partial/mixed):

```json
{
  "response": {
    "responseCode": "45",
    "responseMessage": "Partial Error. Contact your system administrator! RND-PROD-X1: commodityGoodsId or goodsCode does not exist!; RND-PROD-X2: commodityGoodsId or goodsCode does not exist!"
  },
  "data": {
    "voucherNumber": "BT-SAMPLE-001",
    "voucherRef": "BT-SAMPLE-001",
    "sourceBranch": "912550336846912433",
    "destBranch": "592478656342375774",
    "summary": {
      "totalLines": 3,
      "successCount": 1,
      "failureCount": 2
    },
    "lineResults": [
      {
        "lineNo": 1,
        "productCode": "19",
        "qty": "1",
        "status": "SUCCESS",
        "code": "00",
        "message": "The operation was successful"
      },
      {
        "lineNo": 2,
        "productCode": "RND-PROD-X1",
        "qty": "1",
        "status": "ERROR",
        "code": "658",
        "message": "commodityGoodsId or goodsCode does not exist!"
      },
      {
        "lineNo": 3,
        "productCode": "RND-PROD-X2",
        "qty": "1",
        "status": "ERROR",
        "code": "658",
        "message": "commodityGoodsId or goodsCode does not exist!"
      }
    ]
  }
}
```

### 10.18 POST /checktaxpayer

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local",
  "TIN": "9083746521",
  "COMMODITYCODE": "101"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/checktaxpayer" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local",
    "TIN":"9083746521",
    "COMMODITYCODE":"101"
  }'
```

Sample response:

```json
{
  "response": {
    "responseCode": "00",
    "responseMessage": "The operation to check taxpayer was successful"
  },
  "data": {
    "TAXPAYERTYPE": "1",
    "COMMODITYTAXPAYERTYPE": "2"
  }
}
```

### 10.19 POST /voidcreditnote

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local",
  "VOUCHERNUMBER": "CN-001",
  "VOUCHERREF": "ERP-CN-001",
  "VOUCHERTYPE": "Credit Note",
  "VOUCHERTYPENAME": "Credit Note"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/voidcreditnote" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local",
    "VOUCHERNUMBER":"CN-001",
    "VOUCHERREF":"ERP-CN-001",
    "VOUCHERTYPE":"Credit Note",
    "VOUCHERTYPENAME":"Credit Note"
  }'
```

Sample response:

```json
{
  "response": {
    "responseCode": "00",
    "responseMessage": "The operation was successful"
  },
  "data": []
}
```

### 10.20 POST / (index)

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
  "ORGTIN": "9083746521",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"X7mQ2pL9vT4kN8rJc1D5sH0yBzE6uW3aFqR8nM2",
    "ORGTIN":"9083746521",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local"
  }'
```

Sample response:

```json
{
  "response": {
    "responseCode": "00",
    "responseMessage": "It Works!"
  },
  "data": []
}
```

Version history is maintained in section 0, "Version and Change Tracking".
