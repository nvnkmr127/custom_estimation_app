### Implementation Plan - Fix Estimate Creation Errors

The user is experiencing "Estimate collision" logs which are actually misdiagnosed `NOT NULL` constraint violations on the `tax_1` field.
The database schema has `tax_1` with a `default(0)`, but SQLite/Laravel interactions or strict mode might be causing `null` values passed from PHP to override the default, or the default isn't being applied correctly in this specific environment.

Further, the error handling logic in `EstimateService::createEstimate` blindly assumes any `SQLSTATE[23000]` is a unique key collision, which leads to infinite loops/retries instead of failing fast with a useful error for validation issues.

#### Goals
1.  **Fix Data Integrity**: Ensure `tax_1` and `tax_2` are always set (defaulting to 0) in the `estimate` data array before creation.
2.  **Refine Error Handling**: Differentiate between "Unique Key Violation" and generic "Integrity Violation" in the retry logic.
3.  **Confirm DB Sequence Logic**: Preserve the new robust `generateNextNumberV2` logic while integrating these fixes.

#### Proposed Changes

**File:** `app/Services/EstimateService.php`

1.  **Update `createEstimate` Method**:
    *   Pre-process `$data`:
        ```php
        $data['tax_1'] = $data['tax_1'] ?? 0;
        $data['tax_2'] = $data['tax_2'] ?? 0;
        $data['discount_value'] = $data['discount_value'] ?? 0;
        ```
    *   Refine `catch` block:
        ```php
        if (str_contains($e->getMessage(), 'UNIQUE constraint failed') || str_contains($e->getMessage(), 'Duplicate entry')) {
            // Collision logic
        } else {
            // Re-throw immediately (e.g. NOT NULL constraint)
            throw $e;
        }
        ```

#### Verification Plan
1.  **Manual Test**: Trigger `php artisan serve` and ask user to retry.
2.  **Code Review**: Verify defaults are applied.
