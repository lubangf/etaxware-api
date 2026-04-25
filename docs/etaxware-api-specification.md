# eTaxWare API Specification

Last updated: 2026-04-25

## 0. Version and Change Tracking

| Version | Date | Changes |
| --- | --- | --- |
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
- Route methods: POST only

## 2. Base URL and Transport

Use your deployment base, for example:

- Local XAMPP: `http://localhost/etaxware-api`

All routes in the active adapter are defined as POST routes.

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

## 5. Endpoint Catalog

### 5.1 Routed endpoints from config/routes.ini

| Route | Handler | Runtime status |
| --- | --- | --- |
| `/` | `Api->index` | Implemented |
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

#### POST /

- Purpose: service health check
- Handler: `index()`
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
