# Cashbook Reference — Cash Book for the Year 2026.xlsx

## Company Info
- **Name:** Kasabazaar Limited / Rose Door to Door and Delivery Company
- **Address:** Plot Number 17, Block A, Adako Jachie, Ejisu
- **Postal:** P.O Box KJ 69, Kejetia-Ashanti Region, Ghana
- **Phone:** 050 9725073 | **Email:** j.wadel@yahoo.com
- **Director:** John William Adel
- **Business:** Shipping, Door-to-Door Delivery, Property/Construction

---

## Excel Sheet Structure (19 Sheets)

| Sheet | Purpose |
|-------|---------|
| Shipment Details | Per-shipment expense breakdown |
| Jan 26 – Dec 26 | Monthly cashbooks (12 sheets) |
| Income Ledger | Cumulative income ledger by category |
| Expenditure Ledger | Cumulative expense ledger by category |
| Director's Account | Director's fund contributions |
| Trail Balance-2025 | Trial balance for December 2025 |
| Loan Schudle | Loan repayment schedule |
| Fin. Report | Income & Expenditure Statement / Financial Reports |

---

## Monthly Cashbook Sheet Layout

### Header Rows (rows 1–6):
| Row | Content |
|-----|---------|
| 1 | Company name: ROSE DOOR TO DOOR AND DELIEVERY COMPANY |
| 2 | Bank name: REPUBLIC BANK/MOMO |
| 3 | Month/Year label (e.g. JANUARY'2026) |
| 4 | Section headers: RECEIPTS (col M) \| EXPENDITURE (col W) |
| 5 | Column headers |
| 6 | Currency labels (GH₵) |

### Column Map (44 columns total):

**Core Transaction Columns (cols 1–11):**
| Col# | Header | Description |
|------|--------|-------------|
| 1 | DATE | Transaction date |
| 2 | PV NO# | Payment Voucher number |
| 3 | DETAILS | Transaction description |
| 4 | CHQ # | Payment method: BL = Bank cheque, Momo = Mobile Money |
| 5 | BANK DEBIT | Money **received into** bank account |
| 6 | MOMO DEBIT | Money **received into** MOMO account |
| 7 | BANK CREDIT | Money **paid out from** bank account |
| 8 | MOMO CREDIT | Money **paid out from** MOMO account |
| 9 | BANK BALANCE | Running bank balance |
| 10 | MOMO BALANCE | Running MOMO balance |
| 11 | COST CENTER | Transaction category tag |

**Receipt Analysis Columns (cols 13–21):**
| Col# | Category | Notes |
|------|----------|-------|
| 13 | OP. BALANCE | Opening balance only (row 7) |
| 14 | SALES | Construction/property sales income |
| 15 | DIR. TRANSFER | Director's fund injections |
| 16 | SHIPPING FEE | Shipping fees collected from clients |
| 17 | SERVICE FEE | Service charge income |
| 18 | MOMO INTEREST RECEIVED | MTN interest earned on MOMO balance |
| 19 | PROPERTY MANAGEMENT | Property management income |
| 20 | REFUND | Refunds received |
| 21 | CONTRA | Funds received from inter-account transfer |

**Expenditure Analysis Columns (cols 23–44):**
| Col# | Category | Notes |
|------|----------|-------|
| 23 | IMPORT DUTY CHARGES | Customs/duty fees for shipments |
| 24 | SHIPPING EXPENSES | Operational shipping costs (loading, delivery, etc.) |
| 25 | SALARIES & WAGES | Staff salaries |
| 26 | BANK CHARGES | Republic Bank fees |
| 27 | SSNIT | Social Security contributions |
| 28 | PAYE | Pay As You Earn income tax |
| 29 | WITHHOLDING TAX | Supporting staff withholding tax (7.5%) |
| 30 | MOMO CHARGES | Mobile Money transaction fees |
| 31 | CONTRA | Funds sent via inter-account transfer |
| 32 | TRANSPORTATION | Transport (bank runs, SSNIT visits, etc.) |
| 33 | MATERIALS | Construction/project materials |
| 34 | DONATION | Donations made |
| 35–43 | (future/additional) | Extra expense categories as needed |
| 44 | (Grand Total) | Sum of all expenditure analysis columns |

---

## Balance Calculation Formulas

### Running Balance (row by row):
```
BANK BALANCE  = Previous BANK BALANCE  + BANK DEBIT  - BANK CREDIT
MOMO BALANCE  = Previous MOMO BALANCE  + MOMO DEBIT  - MOMO CREDIT
```

### Opening Balance Row (row 7):
```
BANK BALANCE  = BANK DEBIT  (no prior row)
MOMO BALANCE  = MOMO DEBIT  (no prior row)
```
Example (Jan 2026):
- BANK DEBIT = 332.93 → BANK BALANCE = 332.93
- MOMO DEBIT = 83.50  → MOMO BALANCE = 83.50

### Closing Balance Verification (Jan 2026):
```
Closing Bank  = Opening(332.93) + Total Bank Debits(142,526) - Total Bank Credits(112,603.61)
              = 30,255.32 ✓

Closing MOMO  = Opening(83.50) + Total MOMO Debits(120,241.30) - Total MOMO Credits(113,203.66)
              = 7,121.14 ✓
```

### Month-to-Month Carry Forward:
```
Next month Opening Bank Balance  = Current month Closing Bank Balance
Next month Opening MOMO Balance = Current month Closing MOMO Balance
```
Example:
- Jan 2026 closing: Bank = 30,255.32 | MOMO = 7,121.14
- Feb 2026 opening: Bank = 30,255.32 | MOMO = 7,121.14 ✓

---

## Analysis Column Rules (how each row is categorized)

Each transaction row has exactly ONE non-zero entry in the analysis columns (either receipt or expenditure side), based on its COST CENTER:

| COST CENTER | Side | Analysis Column Populated | Value Used |
|-------------|------|--------------------------|-----------|
| OP. BALANCE | Receipt | Col 13 | BANK DEBIT (opening bank balance) |
| SALES | Receipt | Col 14 | BANK DEBIT or MOMO DEBIT |
| DIR. TRANSFER | Receipt | Col 15 | BANK DEBIT or MOMO DEBIT |
| SHIPPING FEE | Receipt | Col 16 | BANK DEBIT or MOMO DEBIT |
| SERVICE FEE | Receipt | Col 17 | BANK DEBIT or MOMO DEBIT |
| MOMO INTEREST RECEIVED | Receipt | Col 18 | MOMO DEBIT |
| PROPERTY MANAGEMENT | Receipt | Col 19 | BANK DEBIT or MOMO DEBIT |
| REFUND | Receipt | Col 20 | BANK DEBIT or MOMO DEBIT |
| CONTRA (receipt) | Receipt | Col 21 | BANK DEBIT (received from MOMO) |
| IMPORT DUTY CHARGES | Expenditure | Col 23 | BANK CREDIT or MOMO CREDIT |
| SHIPPING EXPENSES | Expenditure | Col 24 | BANK CREDIT or MOMO CREDIT |
| SALARIES & WAGES | Expenditure | Col 25 | BANK CREDIT |
| BANK CHARGES | Expenditure | Col 26 | BANK CREDIT |
| SSNIT | Expenditure | Col 27 | BANK CREDIT |
| PAYE | Expenditure | Col 28 | MOMO CREDIT |
| WITHHOLDING TAX | Expenditure | Col 29 | MOMO CREDIT |
| MOMO CHARGES | Expenditure | Col 30 | MOMO CREDIT |
| CONTRA (payment) | Expenditure | Col 31 | MOMO CREDIT (sent to Bank) |
| TRANSPORTATION | Expenditure | Col 32 | MOMO CREDIT |
| MATERIALS | Expenditure | Col 33 | MOMO CREDIT or BANK CREDIT |
| DONATION | Expenditure | Col 34 | BANK CREDIT |

---

## CONTRA (Inter-Account Transfer) Logic

When transferring money **from MOMO to Bank**:
- BANK DEBIT  = transfer amount (bank receives)
- MOMO CREDIT = transfer amount (MOMO sends)
- Receipt analysis col 21 (CONTRA) = amount
- Expenditure analysis col 31 (CONTRA) = amount
- Net effect: bank balance up, MOMO balance down

Example (Jan 08, 2026):
```
Transfer from Momo to Bank: GH₵10,500
  BANK DEBIT  = 10,500  → Bank Balance: 401.93 + 10,500 = 10,901.93
  MOMO CREDIT = 10,500  → MOMO Balance: 10,747.31 - 10,500 = 247.31
  Receipt CONTRA  (col 21) = 10,500
  Expenditure CONTRA (col 31) = 10,500
```

---

## Monthly Totals Row

The last data row of each monthly sheet sums all columns:

| Column | Jan 2026 Total | Feb 2026 Total |
|--------|---------------|---------------|
| BANK DEBIT | 142,526.00 | 199,472.00 |
| MOMO DEBIT | 120,241.30 | 24,615.31 |
| BANK CREDIT | 112,603.61 | 216,131.00 |
| MOMO CREDIT | 113,203.66 | 30,913.15 |
| **RECEIPTS:** | | |
| OP. BALANCE | 332.93 | 30,255.32 |
| SALES | 0 | 3,690.00 |
| DIR. TRANSFER | 78,360.00 | 155,000.00 |
| SHIPPING FEE | 135,725.93 | 52,221.31 |
| MOMO INTEREST | 40.97 | 0 |
| CONTRA (receipt) | 10,500.00 | 0 |
| **EXPENDITURES:** | | |
| IMPORT DUTY | 70,140.00 | 133,480.00 |
| SHIPPING EXPENSES | 100,921.00 | 45,970.00 |
| SALARIES & WAGES | 19,200.00 | 19,200.00 |
| BANK CHARGES | 275.00 | 325.00 |
| SSNIT | 1,948.61 | 1,857.00 |
| PAYE | 1,969.13 | 1,841.95 |
| WITHHOLDING TAX | 603.00 | 651.89 |
| MOMO CHARGES | 150.53 | 78.31 |
| CONTRA (payment) | 10,500.00 | 0 |
| TRANSPORTATION | 100.00 | 100.00 |
| MATERIALS | 20,000.00 | 0 |
| DONATION | 0 | 13,000.00 |

---

## Withholding Tax (WHT) Calculation

Rate: **7.5%** of gross payment amount (applies to supporting/casual staff).

Formula:
```
WHT = Gross Amount × 0.075
```

Example — Dec 2025 WHT (paid in Jan 2026):
| Name | Gross Amount (GH₵) | Rate | WHT (GH₵) |
|------|-------------------|------|-----------|
| Abigail Amaoko Atta snr | 1,500.00 | 7.5% | 112.50 |
| Ahmed Ahia Feehi | 2,000.00 | 7.5% | 150.00 |
| Eshun Dennis Joe | 500.00 | 7.5% | 37.50 |
| Adu Anthony | 500.00 | 7.5% | 37.50 |
| Maxwell Owusu | 2,040.00 | 7.5% | 153.00 |
| Edinam Offridam | 1,500.00 | 7.5% | 112.50 |
| **TOTAL** | **8,040.00** | | **603.00** |

---

## SSNIT Calculation

Ghana Social Security & National Insurance Trust:
- **Employee contribution:** 5.5% of basic salary
- **Employer contribution:** 13% of basic salary
- Total paid to SSNIT = Employer portion only OR combined (depending on company policy)
- Jan 2026: GH₵1,948.61 | Feb 2026: GH₵1,857.00
- Paid via bank cheque (BL) the month after salary

---

## PAYE Calculation

Pay As You Earn income tax (Ghana Revenue Authority rates):
- Deducted from employee salaries and remitted to GRA
- Paid via MOMO in this company
- Jan 2026: GH₵1,969.13 | Feb 2026: GH₵1,841.95
- Paid the month after salary month

---

## Salary Structure

Monthly payroll totals approximately **GH₵19,200** (consistent Jan & Feb):

| Staff Type | Jan 2026 (GH₵) |
|-----------|---------------|
| Mrs. Kezie Osei (monthly) | 3,300 |
| Main Staff Salary | 7,860 |
| Supporting Staff Allowance | 7,040 |
| **Total** | **19,200** |

Previous month salary is paid in two tranches in the following month:
- Part-Payment (early month) via Bank
- Final Payment (mid-month) via Bank

---

## MOMO Charges Calculation

MTN Mobile Money charges a fee on each outgoing transaction:
- Typical charge per transaction: GH₵15.00 flat (for amounts ~GH₵1,000–10,000)
- Smaller amounts attract proportional charges (e.g. GH₵1.47 on GH₵196)
- Every MOMO outgoing payment generates a separate MOMO CHARGES row immediately after

Pattern in cashbook:
```
[Transaction row]  MOMO CREDIT = X
[MOMO Charges row] MOMO CREDIT = fee (typically 15.00)
```

---

## Bank Reconciliation Statement

Appears at the bottom of each monthly sheet (after the totals row).

Format:
```
Balance as per Cash Book                          GH₵ XX,XXX.XX
Add: Unpresented Cheques (issued, not cleared)
  [Date | Details | Cheque # | Amount]             GH₵      0.00
Less: Uncredited Cheques (received, not banked)    GH₵      0.00
                                                  ---------
Balance as per Bank Statement                     GH₵ XX,XXX.XX
                                                  =========
Prepared By: _______________  Date: __________
Approved By: _______________  Date: __________
```

Jan 2026 reconciliation:
- Cash Book Balance = GH₵30,255.32
- No unpresented or uncredited cheques
- Bank Statement Balance = GH₵30,255.32 ✓

Feb 2026:
- Cash Book Balance = GH₵13,596.32
- Bank Statement Balance = GH₵13,596.32 ✓

---

## Shipment Details Sheet

Tracks costs per shipment. Each shipment can have multiple expense memos (Memo 1, Memo 2, etc.).

Columns:
| Col | Field |
|-----|-------|
| DATE | Date of expense |
| MEMO NO | e.g. "Shipping Expenses 1", "Shipping Expenses 2" |
| SHIPMENT NO | e.g. "45th Shipment", "46th Shipment" |
| IMPORT DUTY CHARGES | |
| FEEDING | Staff meals during shipment |
| INTERNAL TRAVELS | Local transport |
| LOADING CHARGES | Loading at port/warehouse |
| ACCRA DELIVERY | Delivery within Accra |
| PORT TO WAREHOUSE | Port clearance to warehouse |
| ACCRA TO KUMASI | Inter-city transport |
| KUMASI DELIVERY | Delivery within Kumasi |
| STATIONARIES | Stationery/documents |
| PORT ENTRY | Port entry fees |
| COMMUNICATION | Phone/communication costs |
| REPLACEMENT | Replacement/repair costs |
| POLICE | Police escort fees |
| Gas | Fuel |
| **TOTAL** | Sum of all expense columns |

Total formula per row:
```
TOTAL = IMPORT_DUTY + FEEDING + INTERNAL_TRAVELS + LOADING + ACCRA_DELIVERY
      + PORT_TO_WAREHOUSE + ACCRA_TO_KUMASI + KUMASI_DELIVERY
      + STATIONARIES + PORT_ENTRY + COMMUNICATION + REPLACEMENT + POLICE + GAS
```

Example — 45th Shipment (Jan 2026):
| Memo | Import Duty | Feeding | Travels | Loading | Accra Del. | Port-WH | Accra-Kumasi | Kumasi Del. | Stat. | Gas | Total |
|------|------------|---------|---------|---------|------------|---------|-------------|------------|-------|-----|-------|
| Memo 1 | 5,000 | 700 | 954 | 2,200 | 4,000 | 2,500 | 6,000 | 0 | 82 | 0 | 21,576 |
| Memo 2 | 0 | 300 | 646 | 1,500 | 4,500 | 0 | 0 | 4,500 | 0 | 100 | 11,546 |

---

## Income Ledger Sheet

Two parallel ledgers side by side. Each updates cumulatively across the year.

Columns per ledger: `MONTH | PARTICULARS | OP. BALANCE | DR. | CR. | CL. BALANCE`

Closing Balance formula:
```
CL. BALANCE = OP. BALANCE + CR. - DR.
```
For income ledgers, DR is normally 0 (income only grows):
```
CL. BALANCE = OP. BALANCE + CR.
```

Ledgers:
1. **Construction Income Ledger** — CR feeds from SALES column in monthly cashbooks
2. **Shipping Fee Ledger** — CR feeds from SHIPPING FEE column in monthly cashbooks

Running example — Shipping Fee Ledger:
| Month | Particulars | OP. Balance | DR | CR | CL. Balance |
|-------|------------|------------|-----|-----|------------|
| JAN | Cash Book Receipt | 0 | 0 | 135,725.93 | 135,725.93 |
| FEB | Cash Book Receipt | 135,725.93 | 0 | 52,221.31 | 187,947.24 |
| MAR | Cash Book Receipt | 187,947.24 | 0 | 0 | 187,947.24 |

---

## Expenditure Ledger Sheet

19 expense ledgers side by side. Each updates cumulatively across the year.

Columns per ledger: `MONTH | PARTICULARS | OP. BALANCE | DR. | CR. | CL. BALANCE`

Closing Balance formula:
```
CL. BALANCE = OP. BALANCE + DR. - CR.
```
For expense ledgers, CR is normally 0 (expenses only accumulate):
```
CL. BALANCE = OP. BALANCE + DR.
```

All 19 ledgers:
1. Material Ledger
2. Audit Fee Ledger
3. Committee Allowance Ledger
4. Salaries & Wages Ledger
5. SSNIT Ledger
6. Transportation Ledger
7. Workmanship Ledger
8. Bank Charges Ledger
9. Telephone/Internet Exp. Ledger
10. Board Meeting Allowance Ledger
11. Donation Ledger
12. Stationary Exp Ledger
13. Depreciation Ledger
14. Shipping/Import Exp Ledger
15. Property Management Ledger
16. Consultancy Expense Ledger
17. Warehouse WIP Ledger
18. Fixed Assets Ledger
19. Staff Training Ledger

Jan 2026 Expenditure Totals (feeds into ledgers):
| Ledger | Jan DR (GH₵) | CL. Balance |
|--------|-------------|------------|
| Salaries & Wages | 19,200.00 | 19,200.00 |
| SSNIT | 1,948.61 | 1,948.61 |
| Transportation | 1,969.13 | 1,969.13 |
| Workmanship (WHT) | 603.00 | 603.00 |
| Bank Charges | 150.53 | 150.53 |
| Shipping/Import Exp | 70,140.00 | 70,140.00 |
| Donation | 100.00 | 100.00 |
| Material | 20,000.00 | 20,000.00 |

---

## Director's Account Sheet

Tracks all director fund injections into the business.

Columns: `DATE | PARTICULARS | OP. BALANCE | DR. | CR. | CL. BALANCE`

Closing Balance formula:
```
CL. BALANCE = OP. BALANCE + DR.
```

Jan 2026 Director injections (feeds from DIR. TRANSFER in cashbook):
| Date | Amount (GH₵) | Running Total |
|------|-------------|--------------|
| 2026-01-05 | 40,000 | 40,000 |
| 2026-01-08 | 10,660 | 50,660 |
| 2026-01-08 | 10,700 | 61,360 |
| 2026-01-21 | 17,000 | 78,360 |
| 2026-01-21 | 14,105 | 92,465 |
| 2026-01-21 | 2,170 | 94,635 |
| 2026-01-24 | 14,998.40 | 109,633.40 |
| 2026-01-25 | 6,867 | 116,500.40 |

---

## Loan Schedule Sheet

Three concurrent loan accounts tracked side by side.

Formula for all loans:
```
CL. BALANCE = OP. BALANCE - DEBIT (payments reduce balance)
```

**Loan 1 — PS. Joe Quao:**
- Opening balance: $30,850
- Monthly payment: $2,000
- Status: Fully paid by 2025-04-01

| Date | Particulars | OP. Balance | Debit | CL. Balance |
|------|------------|------------|-------|------------|
| 2024-01-01 | Op. Balance | 30,850 | 0 | 30,850 |
| 2024-01-02 | Payment Made | 30,850 | 2,000 | 28,850 |
| 2025-02-02 | Payment Made | 28,850 | 2,000 | 26,850 |
| 2025-04-01 | Cleared | 26,850 | 26,850 | 0 |

**Loan 2 — Staff Loan (Mrs. Tina Arhin):**
- Amount disbursed: GH₵5,000
- Monthly deduction: GH₵500 from salary

**Loan 3 — Mad. Lizzy ($20,000):**
- Opening: $20,000 (interest-inclusive)
- Payments tracked with running balance
- Used for construction project expenses

---

## Trial Balance (Trail Balance-2025)

As at December 2025 — Kasabazaar Limited.

**Non-Current Assets:**
| Asset | DR (GH₵) | CR |
|-------|----------|-----|
| Land & Building WIP | 5,374,300 | 0 |
| Warehouse WIP | 180,447 | 0 |
| Office Furniture | 3,200 | 0 |
| Office Equipment | 8,700 | 0 |
| Intangible Asset | 5,230 | 0 |
| Acc. Depreciation (Intangible) | 0 | 5,230 |

---

## Financial Report (Fin. Report Sheet)

**Jan–Apr 2025 (Construction Dept):**
| Item | GH₵ | USD |
|------|-----|-----|
| Revenue | 378,715.20 | 24,672 |
| Direct Operating Cost | -204,874.89 | -12,540.59 |
| Gross Profit | 173,840.31 | 12,131.41 |
| Gen. & Admin Cost | -63,482.81 | -4,135.70 |
| Profit Before Tax | 110,357.50 | 7,995.71 |
| Finance Cost | -228.84 | -250.94 |
| **Net Profit** | **110,128.66** | **7,744.77** |

**Jan–Aug 2025 (Shipping Dept):**
| Item | GH₵ |
|------|-----|
| Revenue | 2,638,316.27 |
| Direct Operating Cost | -3,050,338.86 |
| Gross Loss | -412,022.59 |
| (Shipping dept running at a loss — costs exceed income) |

---

## Complete Transaction List — January 2026

| Row | Date | Details | CHQ | Bank Dr | MOMO Dr | Bank Cr | MOMO Cr | Bank Bal | MOMO Bal | Cost Center |
|-----|------|---------|-----|---------|---------|---------|---------|---------|---------|------------|
| 7 | 2026-01-01 | Balance b/f | — | 332.93 | 83.50 | 0 | 0 | 332.93 | 83.50 | OP. BALANCE |
| 8 | 2026-01-02 | Shipping Fee Received | Momo | 0 | 8,000 | 0 | 0 | 332.93 | 8,083.50 | SHIPPING FEE |
| 9 | 2026-01-03 | Shipping Fee Received | Momo | 0 | 16,000 | 0 | 0 | 332.93 | 24,083.50 | SHIPPING FEE |
| 10 | 2026-01-04 | 45th Shipping Expenses1 | Momo | 0 | 0 | 0 | 21,576 | 332.93 | 2,507.50 | SHIPPING EXPENSES |
| 11 | 2026-01-04 | Momo Charges | — | 0 | 0 | 0 | 15 | 332.93 | 2,492.50 | MOMO CHARGES |
| 12 | 2026-01-05 | Final Payment 45th Import Duty | Momo | 0 | 0 | 0 | 1,759 | 332.93 | 733.50 | IMPORT DUTY CHARGES |
| 13 | 2026-01-05 | Momo Charges | — | 0 | 0 | 0 | 13.19 | 332.93 | 720.31 | MOMO CHARGES |
| 14 | 2026-01-05 | Director's Funds Received | — | 40,000 | 0 | 0 | 0 | 40,332.93 | 720.31 | DIR. TRANSFER |
| 15 | 2026-01-06 | Import Duty 46th Shipment | BL | 0 | 0 | 39,881 | 0 | 451.93 | 720.31 | IMPORT DUTY CHARGES |
| 16 | 2026-01-06 | Other Bank Charges | — | 0 | 0 | 50 | 0 | 401.93 | 720.31 | BANK CHARGES |
| 17 | 2026-01-06 | Shipping Fee Received | Momo | 0 | 4,047 | 0 | 0 | 401.93 | 4,767.31 | SHIPPING FEE |
| 18 | 2026-01-06 | Accountable Imprest 46th Shipment | Momo | 0 | 0 | 0 | 4,000 | 401.93 | 767.31 | SHIPPING EXPENSES |
| 19 | 2026-01-06 | Momo Charges | — | 0 | 0 | 0 | 15 | 401.93 | 752.31 | MOMO CHARGES |
| 20 | 2026-01-08 | Director's Funds Received | — | 0 | 10,660 | 0 | 0 | 401.93 | 11,412.31 | DIR. TRANSFER |
| 21 | 2026-01-08 | 45th Shipping Expenses 2 (Part) | Momo | 0 | 0 | 0 | 11,350 | 401.93 | 62.31 | SHIPPING EXPENSES |
| 22 | 2026-01-08 | Momo Charges | — | 0 | 0 | 0 | 15 | 401.93 | 47.31 | MOMO CHARGES |
| 23 | 2026-01-08 | Director's Funds Received | Momo | 0 | 10,700 | 0 | 0 | 401.93 | 10,747.31 | DIR. TRANSFER |
| 24 | 2026-01-08 | Transfer MOMO→Bank (Contra) | — | 10,500 | 0 | 0 | 10,500 | 10,901.93 | 247.31 | CONTRA |
| 25 | 2026-01-08 | Momo Charges | — | 0 | 0 | 0 | 15 | 10,901.93 | 232.31 | MOMO CHARGES |
| 26 | 2026-01-08 | Dec 2025 Salary (Part-Payment) | BL | 0 | 0 | 10,040 | 0 | 861.93 | 232.31 | SALARIES & WAGES |
| 27 | 2026-01-08 | Other Bank Charges | — | 0 | 0 | 150 | 0 | 711.93 | 232.31 | BANK CHARGES |
| 28 | 2026-01-08 | Shipping Fee (Mr. Kelvin) | Momo | 0 | 15,400 | 0 | 0 | 711.93 | 15,632.31 | SHIPPING FEE |
| 29 | 2026-01-11 | Final Payment 45th Shipping Exp. | Momo | 0 | 0 | 0 | 196 | 711.93 | 15,436.31 | SHIPPING EXPENSES |
| 30 | 2026-01-11 | Momo Charges | — | 0 | 0 | 0 | 1.47 | 711.93 | 15,434.84 | MOMO CHARGES |
| 31 | 2026-01-12 | 46th Shipping Exp 1 (Part) | Momo | 0 | 0 | 0 | 10,000 | 711.93 | 5,434.84 | SHIPPING EXPENSES |
| 32 | 2026-01-12 | Momo Charges | — | 0 | 0 | 0 | 15 | 711.93 | 5,419.84 | MOMO CHARGES |
| 33 | 2026-01-13 | Shipping Fee Received | — | 8,170 | 0 | 0 | 0 | 8,881.93 | 5,419.84 | SHIPPING FEE |
| 34 | 2026-01-13 | Dec 2025 Payment (Gergor Adu Poku) | — | 3,450 | 0 | 0 | 0 | 12,331.93 | 5,419.84 | SHIPPING FEE |
| 35 | 2026-01-14 | Dec 2025 Salary (Final Payment) | BL | 0 | 0 | 9,160 | 0 | 3,171.93 | 5,419.84 | SALARIES & WAGES |
| 36 | 2026-01-14 | Dec 2025 Staff SSNIT | Chq 000005 | 0 | 0 | 1,948.61 | 0 | 1,223.32 | 5,419.84 | SSNIT |
| 37 | 2026-01-13 | Shipping Fee Received | — | 6,420 | 0 | 0 | 0 | 7,643.32 | 5,419.84 | SHIPPING FEE |
| 38 | 2026-01-14 | Staff PAYE Dec 2025 | Momo | 0 | 0 | 0 | 1,969.13 | 7,643.32 | 3,450.71 | PAYE |
| 39 | 2026-01-14 | Supporting Staff WHT | Momo | 0 | 0 | 0 | 603 | 7,643.32 | 2,847.71 | WITHHOLDING TAX |
| 40 | 2026-01-14 | Transportation to Bank/SSNIT | Momo | 0 | 0 | 0 | 100 | 7,643.32 | 2,747.71 | TRANSPORTATION |
| 41 | 2026-01-14 | Momo Charges | Momo | 0 | 0 | 0 | 15.87 | 7,643.32 | 2,731.84 | MOMO CHARGES |
| 42 | 2026-01-14 | 46th Shipping Exp 1 (Part) | Momo | 0 | 0 | 0 | 2,500 | 7,643.32 | 231.84 | SHIPPING EXPENSES |
| 43 | 2026-01-14 | 46th Shipping Exp 1 (Part) | BL | 0 | 0 | 7,000 | 0 | 643.32 | 231.84 | SHIPPING EXPENSES |
| 44 | 2026-01-14 | Momo Charges | — | 0 | 0 | 0 | 15 | 643.32 | 216.84 | MOMO CHARGES |
| 45 | 2026-01-16 | Shipping Fee Received | Momo | 0 | 2,934.93 | 0 | 0 | 643.32 | 3,151.77 | SHIPPING FEE |
| 46 | 2026-01-17 | Shipping Fee Received | Momo | 0 | 7,581 | 0 | 0 | 643.32 | 10,732.77 | SHIPPING FEE |
| 47 | 2026-01-19 | Shipping Fee (Ebenezar Boahen) | Momo | 0 | 1,736 | 0 | 0 | 643.32 | 12,468.77 | SHIPPING FEE |
| 48 | 2026-01-21 | Director's Funds Received | Momo | 0 | 14,105 | 0 | 0 | 643.32 | 26,573.77 | DIR. TRANSFER |
| 49 | 2026-01-21 | Director's Funds Received | Momo | 0 | 2,170 | 0 | 0 | 643.32 | 28,743.77 | DIR. TRANSFER |
| 50 | 2026-01-21 | 47th Import Duty Part-Payment | Momo | 0 | 0 | 0 | 28,500 | 643.32 | 243.77 | IMPORT DUTY CHARGES |
| 51 | 2026-01-21 | Momo Charges | — | 0 | 0 | 0 | 15 | 643.32 | 228.77 | MOMO CHARGES |
| 52 | 2026-01-21 | Director's Funds Received | — | 17,000 | 0 | 0 | 0 | 17,643.32 | 228.77 | DIR. TRANSFER |
| 53 | 2026-01-21 | Rent & 47th Shipping Exp 1 | Chq 000006 | 0 | 0 | 14,701 | 0 | 2,942.32 | 228.77 | SHIPPING EXPENSES |
| 54 | 2026-01-23 | Shipping Fee Received | — | 13,000 | 0 | 0 | 0 | 15,942.32 | 228.77 | SHIPPING FEE |
| 55 | 2026-01-24 | MTN Interest Received | — | 0 | 40.97 | 0 | 0 | 15,942.32 | 269.74 | MOMO INTEREST RECEIVED |
| 56 | 2026-01-24 | Director's Funds Received | — | 0 | 14,998.40 | 0 | 0 | 15,942.32 | 15,268.14 | DIR. TRANSFER |
| 57 | 2026-01-25 | Director's Funds Received | — | 0 | 6,867 | 0 | 0 | 15,942.32 | 22,135.14 | DIR. TRANSFER |
| 58 | 2026-01-25 | Bortianor Project (E.K Manu) | Momo | 0 | 0 | 0 | 20,000 | 15,942.32 | 2,135.14 | MATERIALS |
| 59 | 2026-01-25 | Momo Charges | — | 0 | 0 | 0 | 15 | 15,942.32 | 2,120.14 | MOMO CHARGES |
| 60 | 2026-01-26 | 47th Shipping Expenses 2 | BL | 0 | 0 | 10,831 | 0 | 5,111.32 | 2,120.14 | SHIPPING EXPENSES |
| 61 | 2026-01-26 | 47th Shipping Expenses 2 | BL | 0 | 0 | 4,169 | 0 | 942.32 | 2,120.14 | SHIPPING EXPENSES |
| 62 | 2026-01-27 | Shipping Fee (Mr. Prince) | — | 3,850 | 0 | 0 | 0 | 4,792.32 | 2,120.14 | SHIPPING FEE |
| 63 | 2026-01-28 | Shipping Fee Received | — | 5,470 | 0 | 0 | 0 | 10,262.32 | 2,120.14 | SHIPPING FEE |
| 64 | 2026-01-28 | Claude AI Subscription | BL | 0 | 0 | 2,800 | 0 | 7,462.32 | 2,120.14 | SHIPPING EXPENSES |
| 65 | 2026-01-28 | Final 47th Shipping Exp (M3&4) | BL | 0 | 0 | 6,398 | 0 | 1,064.32 | 2,120.14 | SHIPPING EXPENSES |
| 66 | 2026-01-30 | Other Bank Charges | — | 0 | 0 | 25 | 0 | 1,039.32 | 2,120.14 | BANK CHARGES |
| 67 | 2026-01-30 | Shipping Fee Received | — | 12,976 | 0 | 0 | 0 | 14,015.32 | 2,120.14 | SHIPPING FEE |
| 68 | 2026-01-30 | Shipping Fee Received | — | 0 | 5,001 | 0 | 0 | 14,015.32 | 7,121.14 | SHIPPING FEE |
| 69 | 2026-01-30 | Delivery (Mr Bruno & Abdul) | BL | 0 | 0 | 5,400 | 0 | 8,615.32 | 7,121.14 | SHIPPING EXPENSES |
| 70 | 2026-01-30 | Shipping Fee Received | — | 21,690 | 0 | 0 | 0 | 30,305.32 | 7,121.14 | SHIPPING FEE |
| 71 | 2026-01-31 | Other Bank Charges | — | 0 | 0 | 50 | 0 | **30,255.32** | **7,121.14** | BANK CHARGES |

---

## Database Tables Needed

### 1. `cashbook_entries`
```sql
id, date, pv_no, details, chq_ref,
bank_debit, momo_debit, bank_credit, momo_credit,
bank_balance, momo_balance,
cost_center, month_year,
-- receipt analysis columns:
op_balance, sales, dir_transfer, shipping_fee, service_fee,
momo_interest, property_management, refund, contra_receipt,
-- expenditure analysis columns:
import_duty, shipping_expenses, salaries_wages, bank_charges,
ssnit, paye, withholding_tax, momo_charges, contra_payment,
transportation, materials, donation,
created_at, updated_at
```

### 2. `shipment_expenses`
```sql
id, date, memo_no, shipment_no,
import_duty, feeding, internal_travels, loading_charges,
accra_delivery, port_to_warehouse, accra_to_kumasi,
kumasi_delivery, stationaries, port_entry,
communication, replacement, police, gas, total,
created_at
```

### 3. `income_ledger`
```sql
id, month, year, ledger_type (construction/shipping),
particulars, op_balance, debit, credit, cl_balance
```

### 4. `expenditure_ledger`
```sql
id, month, year,
ledger_type ENUM(material, audit_fee, committee_allowance,
  salaries_wages, ssnit, transportation, workmanship,
  bank_charges, telephone_internet, board_meeting,
  donation, stationary, depreciation, shipping_import,
  property_management, consultancy, warehouse_wip,
  fixed_assets, staff_training),
particulars, op_balance, debit, credit, cl_balance
```

### 5. `director_account`
```sql
id, date, particulars, op_balance, debit, credit, cl_balance
```

### 6. `loans`
```sql
id, lender_name, date, particulars, op_balance, debit, credit, cl_balance
```

### 7. `withholding_tax_breakdown`
```sql
id, month, year, staff_name, gross_amount, rate, wht_amount
```

---

## Key Business Rules for Implementation

1. **Every outgoing MOMO payment** generates a MOMO CHARGES row immediately after
2. **Contra entries** appear symmetrically — both accounts show same amount (one debit, one credit)
3. **Previous month salary** is paid in current month (e.g. Dec salary paid in Jan/Feb)
4. **SSNIT & PAYE** are paid the month FOLLOWING the salary month
5. **WHT** is calculated at **7.5%** per supporting staff member and remitted monthly
6. **Bank balance** only changes when BANK DEBIT or BANK CREDIT is non-zero
7. **MOMO balance** only changes when MOMO DEBIT or MOMO CREDIT is non-zero
8. **Shipments** can span multiple months (expenses split across memos)
9. **Bank Reconciliation** is done monthly and signed off by preparer and approver
10. **Currency**: All amounts in GH₵ (Ghana Cedis); USD used only in Fin. Report
11. **Opening balance row** always uses date = 1st of the month (or last day of prior month)
12. **Contra self-balances**: Receipt CONTRA + Expenditure CONTRA always equal the same amount
13. **Grand Total column (col 44)** = sum of ALL expenditure analysis column totals for the month
14. **Ledgers update monthly**: each month's cashbook totals feed directly into Income & Expenditure Ledgers

---

## Supported Payment Methods (CHQ # column)
| Value | Meaning |
|-------|---------|
| BL | Bank (Republic Bank cheque/transfer) |
| Momo | MTN Mobile Money |
| Chq XXXXXX | Specific cheque number (e.g. Chq 000005) |
| (blank) | Cash or internal transaction |
