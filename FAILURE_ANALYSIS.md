# Silent Failures & Invisible Crashes Analysis

## Executive Summary
This document analyzes the current state of error handling, silent failures, and user-invisible crashes within the application, focusing on Controllers, Jobs, PDF Generation, and API Integrations (`Perfex`, `AI`).

**Overall Health**: Mixed. 
- **Webhooks**: Highly robust with Dead Letter Queues (DLQ) and retry policies.
- **Controllers**: Generally safe but prone to "Generic Error" feedback where specific details are lost.
- **APIs**: The `PerfexApiService` and `AIService` suppress exceptions to prevent crashes, potentially leading to logic errors in calling code if status checks are missed.

---

## 1. Controllers (`EstimateController`)

### Findings
- **Generic Catch Blocks**: In `store()` and `update()`, exceptions are caught and flashed to the session as simple strings (`$e->getMessage()`).
  - **Risk**: Critical system failures (DB Connection, corruption) look the same as logic errors. Full stack traces are often not logged in the catch block (Laravel logs uncaught, but here we catch locally).
- **Bulk Actions**: `bulkUpdate` iterates through items. If one fails authorization, it is silently skipped. The user is told "Processed X estimates", but X might be less than selected.
- **Authorization**: Some methods authorize via policy but don't explicitly handle the `403` response customization, relying on Laravel's default abort.

### Recommendations
- **Explicit Logging**: Enhance catch blocks to `Log::error()` with `trace` before flashing user feedback.
- **Bulk Feedback**: In `bulkUpdate`, track skipped items and inform the user ("Processed 4/5. 1 skipped due to permissions").

## 2. Background Jobs (`SendEmailJob`, `WebhookDeliveryJob`)

### Findings
- **WebhookDeliveryJob**: **Excellent**. Uses `backoff` strategies, handles specific HTTP codes (4xx vs 5xx), and implements a Dead Letter Queue (`WebhookDeadLetter`) for permanent failures.
- **SendEmailJob**: **Basic**.
  - **Risk**: Throws raw `\Exception` on failure. Relies completely on Queue Worker default retry functionality.
  - **Silent Failure**: If the SMTP server rejects credential (auth error), it will retry 3 times then fail silently (to the user). The user has no way to know their estimate code didn't arrive.
  
### Recommendations
- **Failure Events**: Use `failed()` method in `SendEmailJob` to perhaps trigger an in-app notification to the sender ("Email delivery failed").

## 3. PDF Generation (`PdfRenderingService`)

### Findings
- **Memory Limit**: `ini_set('memory_limit', '512M')` helps, but is a patch.
- **Silent Return**: `renderAndCache` catches **all** exceptions and returns `null`. 
- **User Feedback**: Controller checks for `null` and says "Failed to generate PDF". 
  - **Issue**: Debugging is hard. If a specific image fails to load (DOMPDF common issue), it logs to file but user/dev might miss it in production.

### Recommendations
- **Validation**: Pre-validate images before passing to DOMPDF to avoid timeout/memory crashes.
- **Error Propagation**: Return a specific Error/Status object instead of `null` so the controller can say "Image X failed to load" vs "Memory limit exceeded".

## 4. API Integrations

### Perfex CRM (`PerfexApiService`)
- **Return Type Confusion**: Returns a mix of JSON Arrays (success) or `['status' => false, 'error' => ...]` (failure).
- **Silent failure risk**: Calling code usually expects a List of Leads. If it receives `$leads['status'] = false`, a `foreach($leads as $lead)` loop might iterate over the keys of the error array (`status`, `error`), causing confusing logic bugs or "Trying to get property of non-object" errors.
- **Dev Masking**: In `local` env, it auto-switches to MOCK data on failure. This hides real configuration issues until deployment.

### OpenAI (`AIService`)
- **Suppressed Error**: Catches exception and returns a static string "Error generating description...".
- **Risk**: User saves this string as the actual description.
- **Log**: Does log the error trace, which is good.

---

## Suggested Global Exception Strategy

### 1. Unified Service Response
Instead of returning `mixed` types (Arrays, Nulls, Objects), use a standardized `ServiceResult` class or DTO for internal services (`Perfex`, `AI`).

```php
class ServiceResult {
    public bool $success;
    public $data;
    public ?string $error;
    public ?string $errorCode;
}
```

### 2. Controller "Try-Catch-Log" Macro
Simplify controller logic by moving standard CRUD Exception handling to a shared trait or base controller method that ensures:
1. Error is Logged with User Context & Inputs.
2. User gets a sanitized message.
3. Developers get a Trace ID.

### 3. Queue Failure Visibility
For critical Jobs (Emails, Webhooks), allow the UI to show "Delivery Failed" status.
- Add `delivery_status` column to `estimates`.
- Update it via Job events (`JobFailed`).

### 4. PDF Robustness
- **Isolate**: If possible, move PDF generation to a separate microservice or specialized Lambda function to prevent Main App OOM (Out of Memory) crashes.
- **Sanitize**: Strip remote images if `render` fails once and retry without images as fallback.
