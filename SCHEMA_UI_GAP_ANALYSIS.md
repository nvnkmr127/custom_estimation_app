# Schema vs UI Gap Analysis

## 1. UI Features with No Persisted State

The following UI elements appear in the `Estimates` create/edit views but do not have a direct 1-to-1 persistence mapping or rely on transient state:

*   **Quick Actions (Load Room Template)**: This is a pure UI helper that populates the `sections` and `items` arrays. No specific "Template Used" ID is stored on the Estimate itself, meaning we lose the "link" to the original template after creation. (i.e., if the template updates later, the estimate doesn't know it was based on it).
    *   *Impact*: Low, but limits "Update from Template" features in future.
*   **Calculated Financials in UI**: The frontend calculates `subtotal`, `tax`, `grand_total` in real-time (Alpine.js). These **ARE** persisted, but there is a risk of Drift if the Backend `PriceCalculator` logic differs from the Alpine.js `calculateTotals` logic.
    *   *Recommendation*: Ensure `PriceCalculator` (Backend) is the single source of truth and overrides frontend values on save.

## 2. Columns Unused by UI

The following database columns exist but are not visibly exposed or utilized in the main `create`/`edit` forms:

*   **`estimates.layout_type`**:
    *   *Status*: **MISSING in DB**. The migration `2026_01_30_105456_add_layout_type_to_estimates_table.php` contains an empty `up()` method.
    *   *UI*: No dropdown for "Layout Type" exists in the UI.
*   **`clients.secondary_email` & `clients.secondary_phone`**:
    *   *Status*: Added in migration `2026_02_02_040842`.
    *   *UI*: The `create.blade.php` "Selected Client" card displays `name`, `email`, `property_name`, `property_address`. It **does not** display the secondary contact info, rendering these fields invisible during estimate creation.
*   **`estimates.nurture_status`, `engagement_score`**:
    *   *Status*: Used for backend automation. Not exposed in UI, which is acceptable (system fields).
*   **`estimate_items.is_package`**:
    *   *Status*: Used in backend logic but derived from `section_type` in recent updates.
    *   *Note*: `estimate_sections.is_package` was dropped, but `estimate_items.is_package` remains. The UI uses it.

## 3. Nullable Fields Rendered as Required

*   **`pdf_template_id`**:
    *   *UI*: marked `required` in `create.blade.php`.
    *   *DB*: Likely Nullable (Foreign Key).
    *   *Verdict*: Safe. UI enforcement is stricter than DB, preventing "No Template" errors during PDF generation.
*   **`items` / `sections`**:
    *   *UI*: The form allows submitting an empty estimate (no items).
    *   *DB*: No constraints (separate tables).
    *   *Logic*: `EstimateService` handles creation. An estimate with 0 items is valid in DB (Draft status).

## 4. Migration Improvement Suggestions

### Critical Fixes
1.  **Fix or Revert Empty Migration**:
    *   File: `2026_01_30_105456_add_layout_type_to_estimates_table.php`
    *   Issue: `up()` and `down()` methods are empty.
    *   Action: If `layout_type` is needed, define it. If not, delete the migration file to avoid confusion.

### Optimizations
2.  **Client Sync Fields**:
    *   Since `secondary_email` is meant for CC'ing (presumably), the `SendEmailJob` should be updated to check this column. Currently, `EstimateService::sendToClient` only checks `client->email`.
3.  **Performance**:
    *   Ensure `estimates.client_id`, `estimates.status`, and `estimates.estimate_date` are composite indexed for the `index` dashboard queries (`where client_id = ? order by date desc`).
