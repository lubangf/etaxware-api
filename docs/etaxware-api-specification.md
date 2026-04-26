# eTaxWare API Specification

Last updated: 2026-04-26

## 0. Version and Change Tracking

| Version | Date | Changes |
| --- | --- | --- |
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
- Current active adapter (config): `api/FTS/v12/Api.php`
- Route map source: `config/routes.ini`
- Utility adapter: `util/{adapter}/Utilities.php`
- Protocol: JSON over HTTP
- Route methods: POST for business endpoints, plus GET support on `/` for health/browser probing.

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
| `45` | Partial/mixed batch stock result | `/batchstockin` | Indicates at least one line did not complete cleanly; inspect response message and line-level outcomes. |
| `99` | Generic business failure or record state conflict | Multiple endpoints | Commonly used for not found/already processed/operation not successful conditions. |
| `0099` | User not allowed to perform function | Permission checks in endpoint handlers | Caller authenticated but lacks required operation permission. |
| `-999` | Mandatory business field missing/invalid | Multiple endpoint validators | Common fail-fast validation code (for example missing voucher number, missing reason, missing TIN). |
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

### 5.2.1 Health and diagnostics

#### GET or POST /

- Purpose: service health check
- Handler: `index()`
- Typical success: `00`, `It Works!` (POST with valid standard envelope)
- Browser/quick probe behavior: GET `/` without required request payload returns graceful JSON validation response (for example `1000: No parameters were sent!`) instead of framework error HTML.

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

#### POST /testapi

- Purpose: service health check (alternate)
- Handler: `testapi()`
- Typical success: `00`, `It Works!`

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

#### POST /validatetin

- Purpose: validate and fetch taxpayer details
- Permission: `QUERYTAXPAYER`
- Required fields (endpoint-specific):
  - `TIN`
- Success data fields:
  - `NINBRN`, `LEGALNAME`, `BUSINESSNAME`, `CONTACTNUMBER`, `CONTACTEMAIL`, `ADDRESS`
- Common business errors:
  - `-999` when TIN is missing

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| TIN | Taxpayer Identification Number to validate | Yes | `1017918269` |

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

#### POST /checktaxpayer

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

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| TIN | Taxpayer Identification Number | Yes | `1017918269` |
| COMMODITYCODE | Commodity classification code | Yes | `101` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `-998` / `-999` |
| response.responseMessage | API outcome message | Yes | `The operation to check taxpayer was successful` |
| data.TAXPAYERTYPE | Taxpayer type code | Conditional | `1` |
| data.COMMODITYTAXPAYERTYPE | Commodity taxpayer type code | Conditional | `2` |

#### POST /currencyquery

- Purpose: fetch configured currency rates
- Permission: `FETCHCURRENCYRATES`
- Endpoint-specific fields: none beyond common auth/audit metadata
- Success data:
  - Dynamic object map, example `{ "UGX": "1", "USD": "..." }`

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

#### POST /uploadproduct

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

#### POST /fetchproduct

- Purpose: query product from EFRIS and return tax/status profile
- Permission: `FETCHPRODUCT`
- Key fields:
  - `PRODUCTCODE`
  - `ERPQTY` (optional; normalized to `0` when omitted)
- Response `data` includes product tax/status indicators similar to uploadproduct response.

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

#### POST /stockin

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
| SUPPLIERTIN | Supplier TIN | No | `1017918269` |
| SUPPLIERNAME | Supplier legal name | No | `Sample Supplier` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `99` / `659` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data | Endpoint payload container | Yes | `[]` |

#### POST /batchstockin

- Purpose: add stock for multiple products (batch)
- Permission: `STOCKIN`
- Required fields:
  - `STOCKINTYPE`, `VOUCHERTYPE`, `VOUCHERTYPENAME`, `VOUCHERNUMBER`, `VOUCHERREF`
  - `INVENTORIES[]` with per-line `PRODUCTCODE`, `QTY`, `RATE`
- Conditional fields:
  - `PRODUCTIONDATE` used when `STOCKINTYPE == 103`
  - For non-103 types: optional `SUPPLIERTIN`, `SUPPLIERNAME`
- Behavior note:
  - Services (`serviceMark == 101`) are skipped in batch stockin.
  - `STOCKINTYPE` is normalized through stock-in type mappings before request dispatch and before local stock-adjustment persistence.

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| STOCKINTYPE | Stock-in type code | Yes | `101` |
| VOUCHERTYPE | Voucher type | Yes | `Purchase` |
| VOUCHERTYPENAME | Voucher type display name | Yes | `Purchase` |
| VOUCHERNUMBER | Voucher number | Yes | `GRN-001` |
| VOUCHERREF | Voucher reference | Yes | `ERP-GRN-001` |
| INVENTORIES[] | Inventory line collection | Yes | `[{"PRODUCTCODE":"SP-100","QTY":"10","RATE":"1000"}]` |
| PRODUCTIONDATE | Production date (when `STOCKINTYPE == 103`) | Conditional | `2026-04-25` |
| SUPPLIERTIN | Supplier TIN | No | `1017918269` |
| SUPPLIERNAME | Supplier name | No | `Sample Supplier` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `45` / `-999` / `99` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data | Endpoint payload container | Yes | `[]` |

#### POST /stockout

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
| response.responseMessage | API outcome message | Yes | `The operation was successfully` |
| data | Endpoint payload container | Yes | `[]` |

#### POST /batchstockout

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
| response.responseCode | API outcome code | Yes | `00` / `-999` / `99` / `659` |
| response.responseMessage | API outcome message | Yes | `The operation was successfully` |
| data | Endpoint payload container | Yes | `[]` |

#### POST /stocktransfer

- Purpose: transfer stock between branches
- Permission: `TRANSFERPRODUCTSTOCK`
- Required fields:
  - `PRODUCTCODE`, `SOURCEBRANCH`, `DESTBRANCH`, `QTY`
- Optional fields:
  - `REMARKS`
- Mapping behavior:
  - `SOURCEBRANCH` and `DESTBRANCH` accept ERP-facing branch names/codes and are normalized to mapped branch identifiers before transfer and persistence.
  - `PRODUCTCODE` is resolved to mapped product code (when available) before transfer and persistence.

Accepted parameters:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| See section 3.1 common fields | Authentication and audit metadata (applies to all endpoints) | Yes | `VERSION`, `APIKEY`, `ORGTIN`, `ERPUSER`, `WINDOWSUSER`, `IPADDRESS`, `MACADDRESS`, `SYSTEMNAME` |
| PRODUCTCODE | Product code | Yes | `SP-100` |
| SOURCEBRANCH | Source branch code/name | Yes | `Kampala HQ` |
| DESTBRANCH | Destination branch code/name | Yes | `Jinja` |
| QTY | Quantity to transfer | Yes | `5` |
| REMARKS | Transfer remarks | No | `Branch restock` |

Returned fields:

| Technical name | Business name | Mandatory flag | Sample data |
| --- | --- | --- | --- |
| response.responseCode | API outcome code | Yes | `00` / `99` |
| response.responseMessage | API outcome message | Yes | `The operation was successful` |
| data | Endpoint payload container | Yes | `[]` |

### 5.2.4 Sales, credit note, debit note

#### POST /uploadinvoice

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

#### POST /queryinvoice

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
  "APIKEY": "2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
  "ORGTIN": "1017918269",
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
  "APIKEY": "2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
  "ORGTIN": "1017918269",
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
    "APIKEY":"2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
    "ORGTIN":"1017918269",
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
  "APIKEY": "2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
  "ORGTIN": "1017918269",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local",
  "TIN": "1017918269"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/validatetin" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
    "ORGTIN":"1017918269",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local",
    "TIN":"1017918269"
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
    "CONTACTNUMBER": "",
    "CONTACTEMAIL": "",
    "ADDRESS": ""
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
  "APIKEY": "2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
  "ORGTIN": "1017918269",
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
    "APIKEY":"2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
    "ORGTIN":"1017918269",
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
  "APIKEY": "2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
  "ORGTIN": "1017918269",
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
    "APIKEY":"2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
    "ORGTIN":"1017918269",
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
  "APIKEY": "2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
  "ORGTIN": "1017918269",
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
  "PIECEUNITSMEASUREUNIT": "",
  "PIECEUNITPRICE": "0",
  "PACKAGESCALEVALUE": "0",
  "PIECESCALEVALUE": "0",
  "STOCKPREWARNING": "0",
  "UNITPRICE": "1000",
  "TAXCODE": "01",
  "HSCODE": "101",
  "CUSTOMMEASUREUNIT": "",
  "CUSTOMUNITPRICE": "",
  "CUSTOMPACKAGESCALEDVALUE": "",
  "CUSTOMSCALEDVALUE": "",
  "CUSTOMWEIGHT": ""
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/uploadproduct" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
    "ORGTIN":"1017918269",
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
    "PIECEUNITSMEASUREUNIT":"",
    "PIECEUNITPRICE":"0",
    "PACKAGESCALEVALUE":"0",
    "PIECESCALEVALUE":"0",
    "STOCKPREWARNING":"0",
    "UNITPRICE":"1000",
    "TAXCODE":"01",
    "HSCODE":"101",
    "CUSTOMMEASUREUNIT":"",
    "CUSTOMUNITPRICE":"",
    "CUSTOMPACKAGESCALEDVALUE":"",
    "CUSTOMSCALEDVALUE":"",
    "CUSTOMWEIGHT":""
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
  "APIKEY": "2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
  "ORGTIN": "1017918269",
  "ERPUSER": "manager",
  "WINDOWSUSER": "devuser",
  "IPADDRESS": "127.0.0.1",
  "MACADDRESS": "00-00-00-00-00-00",
  "SYSTEMNAME": "local",
  "BUYERADDRESS": "Kampala",
  "BUYEREMAIL": "",
  "BUYERCITIZENSHIP": "UG",
  "BUYERLEGALNAME": "Sample Buyer",
  "MOBILEPHONE": "",
  "CURRENCY": "UGX",
  "INDUSTRYCODE": "101",
  "BUYERSECTOR": "",
  "BUYERNINBRN": "",
  "BUYERTIN": "",
  "VOUCHERNUMBER": "INV-SAMPLE-001",
  "PROJECTID": "",
  "BRANCH": "",
  "VOUCHERTYPE": "Invoice",
  "BUYERREFERENCENO": "",
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
  "PROJECTNAME": "",
  "PRICEVATINCLUSIVE": "NO",
  "BUYERPASSPORTNUM": "",
  "VOUCHERREF": "INVREF-SAMPLE-001",
  "BUYERPLACEOFBUSI": "Kampala Uganda",
  "BUYERTYPE": "101",
  "NONRESIDENTFLAG": "2",
  "BUYERLINEPHONE": "",
  "DELIVERYTERMCODE": "",
  "REMARKS": "sample"
}
```

Sample curl:

```bash
curl -X POST "http://localhost/etaxware-api/api/{adapter}/uploadinvoice" \
  -H "Content-Type: application/json" \
  --data-raw '{
    "VERSION":"5.0.0",
    "APIKEY":"2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
    "ORGTIN":"1017918269",
    "ERPUSER":"manager",
    "WINDOWSUSER":"devuser",
    "IPADDRESS":"127.0.0.1",
    "MACADDRESS":"00-00-00-00-00-00",
    "SYSTEMNAME":"local",
    "BUYERADDRESS":"Kampala",
    "BUYEREMAIL":"",
    "BUYERCITIZENSHIP":"UG",
    "BUYERLEGALNAME":"Sample Buyer",
    "MOBILEPHONE":"",
    "CURRENCY":"UGX",
    "INDUSTRYCODE":"101",
    "BUYERSECTOR":"",
    "BUYERNINBRN":"",
    "BUYERTIN":"",
    "VOUCHERNUMBER":"INV-SAMPLE-001",
    "PROJECTID":"",
    "BRANCH":"",
    "VOUCHERTYPE":"Invoice",
    "BUYERREFERENCENO":"",
    "BUSINESSNAME":"Sample Buyer",
    "VOUCHERTYPENAME":"Invoice",
    "INVENTORIES":[{"RATE":"1000","DISCOUNTPCT":"0","PRODUCTCODE":"EXC_TEST_3","TAXRATE":"0.18","DISCOUNT":"0","TAXCODE":"01","TOTALWEIGHT":"0","QTY":"1","DISCOUNTFLAG":"2","AMOUNT":"1000"}],
    "PROJECTNAME":"",
    "PRICEVATINCLUSIVE":"NO",
    "BUYERPASSPORTNUM":"",
    "VOUCHERREF":"INVREF-SAMPLE-001",
    "BUYERPLACEOFBUSI":"Kampala Uganda",
    "BUYERTYPE":"101",
    "NONRESIDENTFLAG":"2",
    "BUYERLINEPHONE":"",
    "DELIVERYTERMCODE":"",
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
    "QRCODE": ""
  }
}
```

### 10.8 POST /sendmail

Sample request:

```json
{
  "VERSION": "5.0.0",
  "APIKEY": "2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
  "ORGTIN": "1017918269",
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
    "APIKEY":"2PaYBJn9SEm0RjvnrqD23oVYDULhJDHPDqxoZnol",
    "ORGTIN":"1017918269",
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

Version history is maintained in section 0, "Version and Change Tracking".
