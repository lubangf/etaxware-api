# FTS v11 Postman Runbook (DiscountFlag)

## Files

- Collection: FTS-v11-discountflag.postman_collection.json
- Environment: FTS-v11-discountflag.postman_environment.json

## Import Order

1. Import the environment file.
2. Import the collection file.
3. Select the environment: eTaxWare API Local (FTS v11).

## Set Environment Variables

Populate these before execution:

- apikey
- org_tin
- version
- erp_user
- buyer_tin
- product_code
- original_invoice_number (required for credit note and debit note)

## Recommended Test Sequence

1. Run: Invoice - uploadinvoice (DISCOUNTFLAG=1)
2. Copy the invoice number used into original_invoice_number for note tests if needed.
3. Run: Credit Note - uploadcreditnote (DISCOUNTFLAG=2)
4. Run: Debit Note - uploaddebitnote (DISCOUNTFLAG=1)
5. Run: Negative - uploadinvoice (DISCOUNT present, DISCOUNTFLAG missing)
6. Run: Negative - uploaddebitnote (ORIVOUCHERNUMBER missing)
7. Run: Negative - uploaddebitnote (REASONS missing)

## Expected Response Shape

Each request returns:

- response.responseCode
- response.responseMessage
- data object

Successful business flow should return code 000 or 00 depending on route behavior.

## Known Validation Failures (Useful for Setup Checks)

- 7650: No company TIN was specified
- 1001: No API Key was specified
- 1002: API key invalid/inactive/expired
- 1003: Plugin version mismatch
- -999: DISCOUNT/DISCOUNTPCT provided without a valid DISCOUNTFLAG on an inventory line
- -999: The associated original invoice number was not supplied
- -999: No reason was supplied

## Notes on DISCOUNTFLAG

- Accepted line-level values: 1, 2, YES, NO, Y, N, TRUE, FALSE, 0
- Normalized meaning:
  - 1 or YES-like -> discount applies
  - 2 or NO-like -> no discount

The API computes and forwards normalized discount flags for invoice, credit note, and debit note item lines.

In FTS v11, DISCOUNTFLAG is mandatory whenever DISCOUNT or DISCOUNTPCT is greater than 0 at inventory-line level.
