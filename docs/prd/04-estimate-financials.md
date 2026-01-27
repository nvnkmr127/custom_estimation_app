# PRD: Estimate Financials & Analytics

## 1. Overview
The **Financials** module handles the complex math behind the estimate: Subtotals, Taxes, Discounts, and internal Profitability analysis. It serves two masters: correct billing for the client and margin protection for the business.

## 2. User Stories
- **Accurate Totals**: As a client, I expect the math on my PDF to be exact, including tax rates.
- **Profit Visibility**: As a business owner, I want to see the "Gross Profit" of an estimate *before* I approve it.
- **Cost Tracking**: As an estimator, I want to input internal costs for items to calculate my commissions/margins.

## 3. Functional Requirements

### 3.1 Calculation Hierarchy (Order of Operations)
1.  **Line Item Total**: `Unit Price * Quantity` (Rounded to 2 decimals).
2.  **Subtotal**: Sum of all Line Item Totals.
3.  **Discount**:
    - **Percentage**: `Subtotal * (Rate / 100)`
    - **Fixed**: `Min(FixedAmount, Subtotal)` (Cannot exceed subtotal).
4.  **Taxable Base**:
    - **Default**: `Subtotal - Discount`.
    - **Gross Mode**: `Subtotal` (Configurable via Settings).
5.  **Taxes**:
    - `Tax 1`: `Taxable Base * (Rate 1 / 100)`
    - `Tax 2`: `Taxable Base * (Rate 2 / 100)`
6.  **Grand Total**: `(Subtotal - Discount) + Tax 1 + Tax 2`.

### 3.2 Profit & Margin (Internal Only)
- **Line Item Cost**: `Cost * Quantity`.
- **Total Cost**: Sum of all Line Item Costs.
- **Net Revenue**: `Subtotal - Discount`.
- **Gross Profit**: `Net Revenue - Total Cost`.
- **Margin %**: `(Gross Profit / Net Revenue) * 100`.

*Note: Cost and Profit fields must NEVER be exposed to the `client` in the PDF or Portal.*

### 3.3 Tax Configuration
- **Global Settings**: Default Tax 1 Name/Rate and Tax 2 Name/Rate are pulled from `settings`.
- **Defaults**: When creating an estimate, these defaults populate the estimate's tax fields but can be overridden per estimate.

## 4. Technical Specifications
- **Service Method**: `EstimateService::recalculateTotals(Estimate $estimate)`
- **Observer Pattern**: Calculations should ideally be triggered on any save/update event of `EstimateItem` or `Estimate`.
- **Precision**: strict `round($val, 2)` at every step to avoid floating-point drift.

## 5. UI/UX Requirements
- **Live Summary**: The bottom of the Create/Edit page must show a live tally of:
    - Subtotal
    - Discount (Editable)
    - Tax (Editable Rates)
    - **Total**
- **Profit Toggle**: A specific toggle (visible only to Admins/Estimators) to "Show Margin/Cost" columns in the grid. Clients/Guests never see this.
