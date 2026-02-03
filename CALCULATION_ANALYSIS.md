# Estimate Calculation Verification & Recommendations

## 1. Analysis of Current Calculation Logic

### Duplication
*   **Estimate Service**: `recalculateTotals` (Lines 396-539 in `EstimateService.php`) handles the core logic: Line Items, Subtotals, Discounts, Taxes, Grand Total.
*   **PDF Rendering**: `PdfRenderingService.php` primarily *formats* these values for display but performs some minor logic like converting `size * qty` for display variables (`item_subtotal` variable, line 421).
*   **Frontend**: There is likelihood of duplicated logic in the Javascript (e.g., in `create/edit` forms) to provide real-time updates. This creates a risk of "drifting" values where the live preview differs from the saved database value.

### Floating-Point Precision
*   **Current Approach**: `round($value, 2)` is used liberally throughout `recalculateTotals`.
*   **Risk**: Doing intermediate rounding on *every* line item (`lineTotal`) and then summing them (`$subtotal += $lineTotal`) vs summing unrounded values and rounding at the end can yield different results.
    *   *System*: Sums rounded line totals.
    *   *Standard*: This is generally acceptable for invoices to ensure line items match the sum visually.
*   **Method**: PHP floats are used. While generally fine for small scale, money operations are safer with `bcmath` or integer-based inputs (cents). However, given the `round(..., 2)` enforcement, the risk is managed but not eliminated for high-precision needs.

### Edge Cases
1.  **Empty Sections**: Logic handles this, but `recalculateTotals` iterates `$estimate->items`. If a section exists but has no items, it contributes 0. If a Room-Based estimate has items *without* a section (orphans), the logic still sums them to the global subtotal, which is robust.
2.  **Zero Quantity**: Allowed. Leads to 0 total.
3.  **Negative/Null Costs**: `cost` defaults to 0. `max(0, $grandTotal)` prevents negative estimates.

## 2. Recommendations for Improvement

### A. Centralized Calculation Engine
Currently, logic is embedded in `EstimateService`. This is "okay" but tightly couples persistence with business logic.
**Recommendation:** Extract a `PriceCalculator` class.
*   **Input**: `EstimateDTO` (Data Transfer Object) or just the Model.
*   **Output**: `CalculationResult` (struct with subtotal, tax breakdown, grand_total, margin).
*   **Usage**: Both `EstimateService` (backend) and an API endpoint (frontend calls this for ajax updates) use the *exact same class*.

### B. Precision Hardening
*   **Action**: Switch to **Integer Math** (store everything in cents) OR use a consistent `Money` library (e.g., `brick/money`).
*   **Immediate Fix**: Ensure `round()` is applied consistently. The current code rounds line items, then sums. This is "Banker's Rounding" friendly for display but ensure the Frontend JS does the same.

### C. Tax Logic Refactoring
*   Current: `tax_1` only (GST). Logic ignores `tax_2`.
*   Code: `$tax1Amount = round($taxableAmount * ($estimate->tax_1 / 100), 2);`
*   **Issue**: It applies tax to the *Net Subtotal* (after discount).
*   **Risk**: Some jurisdictions require Tax on Gross, then Discount on Gross.
*   **Status**: Configurable via `tax_calculation_method` setting (lines 402, 458-464 in `EstimateService`). This is **Good**.

## 3. Immediate Action Plan (Micro-Task)
1.  **Refactor**: No immediate critical bugs found in calculation logic itself, but the *Distribution* of this logic to the frontend is the main risk.
2.  **Verify**: Create a test case with:
    *   Item A: 10 * 10.55 = 105.50
    *   Item B: 3 * 33.33 = 99.99
    *   Discount: 10%
    *   Verify Grand Total matches exactly between PHP and JS.

**Conclusion**: The backend logic is sound for the current requirements (Room/Standard support, Area formulas). The primary risk is purely synchronization with the frontend.
