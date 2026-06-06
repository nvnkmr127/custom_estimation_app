# Custom Estimation Application API Documentation

Welcome to the API documentation for the Custom Estimation Application. This document provides developers with the request and response specifications for interacting with the backend services via mobile or REST clients.

---

## 1. Global Request and Response Headers

All API requests should include the following headers for proper serialization and session management:

| Header Name | Value | Description |
| :--- | :--- | :--- |
| `Accept` | `application/json` | **Required** for JSON responses. |
| `Content-Type` | `application/json` | **Required** for POST, PUT, PATCH requests. |
| `X-CSRF-TOKEN` | `[token_string]` | Required if authenticated via web sessions (Laravel CSRF middleware). |
| `X-SSO-Sync-Token` | `[sync_token]` | Required only for the `/api/sso/sync` route. |

---

## 2. Authentication & SSO

### login
**`POST /login`**
Logs in a user and starts a secure session.

*   **Request Body:**
    ```json
    {
      "email": "user@example.com",
      "password": "secret_password"
    }
    ```
*   **Response (JSON when requested with `Accept: application/json`):**
    ```json
    {
      "success": true,
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "role": "estimator",
        "mobile_number": "+1234567890",
        "created_at": "2026-06-06T12:00:00.000000Z"
      },
      "token": "laravel_session_id_hash_string"
    }
    ```

### Register
**`POST /register`**
Registers a new user and logs them in.

*   **Request Body:**
    ```json
    {
      "name": "Jane Doe",
      "email": "jane@example.com",
      "password": "securepassword123",
      "password_confirmation": "securepassword123"
    }
    ```
*   **Response (JSON):**
    ```json
    {
      "success": true,
      "user": {
        "id": 2,
        "name": "Jane Doe",
        "email": "jane@example.com",
        "role": "estimator",
        "created_at": "2026-06-06T21:00:00.000000Z"
      },
      "token": "laravel_session_id_hash_string"
    }
    ```

### Logout
**`POST /logout`**
Destroys the current authenticated session.

*   **Response (JSON):**
    ```json
    {
      "success": true,
      "message": "Logged out successfully."
    }
    ```

### SSO Configurations Sync
**`GET /api/sso/sync`**
Used by the centralized Auth Portal to retrieve application roles and permissions config.

*   **Headers:**
    *   `X-SSO-Sync-Token`: Sync token string
*   **Response (JSON):**
    ```json
    {
      "roles": {
        "super_admin": { "name": "Super Admin", "description": "Full access", "color": "purple" }
      },
      "permissions": {
        "create_estimates": "Create estimates"
      },
      "groups": {
        "Estimates": ["create_estimates", "edit_estimates"]
      }
    }
    ```

---

## 3. Device Push Notification Tokens

These APIs allow the mobile application to register and deregister Expo push notification tokens.

### Register Device Token
**`POST /devices/register`**
Saves or updates a device push token for the authenticated user.

*   **Request Body:**
    ```json
    {
      "token": "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]",
      "platform": "ios"
    }
    ```
*   **Response (JSON):**
    ```json
    {
      "success": true,
      "message": "Device token registered successfully.",
      "device_token": {
        "id": 5,
        "user_id": 1,
        "token": "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]",
        "platform": "ios",
        "created_at": "2026-06-06T21:40:00.000000Z",
        "updated_at": "2026-06-06T21:40:00.000000Z"
      }
    }
    ```

### Deregister Device Token
**`POST /devices/deregister`**
Removes a registered device token (highly recommended on user logout).

*   **Request Body:**
    ```json
    {
      "token": "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]"
    }
    ```
*   **Response (JSON):**
    ```json
    {
      "success": true,
      "message": "Device token deregistered successfully."
    }
    ```

---

## 4. Estimates Management

All estimates endpoints require authentication and obey policies based on roles and resource ownership.

### List Estimates
**`GET /estimates`**
Fetch a list of estimates. Supports pagination.

*   **Response (JSON if requested):**
    ```json
    {
      "current_page": 1,
      "data": [
        {
          "id": 10,
          "estimate_number": "EST-2026-0001",
          "client_id": 3,
          "status": "draft",
          "total_amount": 1250.00,
          "expiry_date": "2026-07-06",
          "created_by": 1
        }
      ]
    }
    ```

### Create Estimate
**`POST /estimates`**
Creates a new estimate.

*   **Request Body:**
    ```json
    {
      "client_id": 3,
      "expiry_date": "2026-07-06",
      "sections": [
        {
          "name": "Living Room",
          "items": [
            {
              "product_id": 12,
              "quantity": 2,
              "unit_price": 450.00,
              "discount_percentage": 5.0
            }
          ]
        }
      ]
    }
    ```
*   **Response (JSON):**
    ```json
    {
      "success": true,
      "estimate": {
        "id": 11,
        "estimate_number": "EST-2026-0002",
        "status": "draft",
        "total_amount": 855.00
      }
    }
    ```

### Update Estimate
**`PUT/PATCH /estimates/{estimate}`**
Updates details or line items of an estimate.

*   **Request Body:** (Same properties as creation, modifying values)
*   **Response (JSON):**
    ```json
    {
      "success": true,
      "message": "Estimate updated successfully."
    }
    ```

### Delete Estimate
**`DELETE /estimates/{estimate}`**
Deletes/soft-deletes an estimate.

*   **Response (JSON):**
    ```json
    {
      "success": true,
      "message": "Estimate deleted successfully."
    }
    ```

### Copy/Duplicate Estimate
**`POST /estimates/{estimate}/copy`**
Duplicates an existing estimate as a new draft.

*   **Response (JSON):**
    ```json
    {
      "success": true,
      "new_estimate_id": 12,
      "message": "Estimate duplicated successfully."
    }
    ```

### Send to Client
**`POST /estimates/{estimate}/send`**
Sends the estimate PDF URL and email invitation to the client.

*   **Response (JSON):**
    ```json
    {
      "success": true,
      "message": "Estimate sent to client successfully."
    }
    ```

### Mark Status
**`POST /estimates/{estimate}/mark-as/{status}`**
Directly updates an estimate's status (e.g. `sent`, `accepted`, `declined`, `expired`).

*   **Response (JSON):**
    ```json
    {
      "success": true,
      "new_status": "accepted",
      "message": "Estimate marked as accepted."
    }
    ```

---

## 5. Approval Chain Workflows

Estimates require approvals based on validation, margin values, or admin rules.

### Submit for Approval
**`POST /estimates/{estimate}/submit`**
Submits the estimate to begin the approval chain routing.

*   **Response (JSON):**
    ```json
    {
      "success": true,
      "message": "Submitted for approval successfully."
    }
    ```

### Approve Step
**`POST /estimates/{estimate}/approve`**
Approves the current step in the approval chain.

*   **Response (JSON):**
    ```json
    {
      "success": true,
      "message": "Estimate approved successfully at current step."
    }
    ```

### Reject Estimate
**`POST /estimates/{estimate}/reject`**
Rejects the estimate and sends it back to the creator.

*   **Request Body:**
    ```json
    {
      "reason": "Margin is too low. Please raise item price."
    }
    ```
*   **Response (JSON):**
    ```json
    {
      "success": true,
      "message": "Estimate rejected."
    }
    ```

---

## 6. Comments & Discussion

Allows estimators and administrators to discuss and review line items.

### List Comments
**`GET /estimates/{estimate}/comments`**
Retrieves the discussion thread for an estimate.

*   **Response (JSON):**
    ```json
    [
      {
        "id": 1,
        "estimate_id": 10,
        "user_id": 2,
        "content": "Is the living room paint discount correct?",
        "created_at": "2026-06-06T14:10:00.000000Z",
        "user": {
          "name": "Jane Admin"
        }
      }
    ]
    ```

### Post Comment
**`POST /estimates/{estimate}/comments`**
Posts a comment or a reply to an estimate thread.

*   **Request Body:**
    ```json
    {
      "content": "Yes, it is within the allowed limits.",
      "parent_id": null
    }
    ```
*   **Response (JSON):**
    ```json
    {
      "success": true,
      "comment": {
        "id": 2,
        "content": "Yes, it is within the allowed limits.",
        "created_at": "2026-06-06T14:15:00.000000Z"
      }
    }
    ```

---

## 7. Clients Directory

### List Clients
**`GET /clients`**
Returns pageable client directories.

*   **Response (JSON):**
    ```json
    {
      "data": [
        {
          "id": 3,
          "name": "Acme Corp",
          "email": "contact@acme.com",
          "phone": "+1987654321"
        }
      ]
    }
    ```

---

## 8. Products Library

### Suggest Details (GenAI)
**`POST /products/suggest`**
Uses Generative AI to suggest descriptive metadata, tags, and packaging rules for a product.

*   **Request Body:**
    ```json
    {
      "name": "Teak Wood Dining Table",
      "attributes": ["6-seater", "rustic finish"]
    }
    ```
*   **Response (JSON):**
    ```json
    {
      "suggested_description": "Crafted from premium teak wood, this 6-seater dining table brings a warm rustic elegance to any space.",
      "suggested_category": "Furniture"
    }
    ```

---

## 9. Reminders & Tasks

### List Reminders
**`GET /reminders`**
Gets active dynamic alerts and reminders for the current user.

*   **Response (JSON):**
    ```json
    [
      {
        "id": 4,
        "title": "Follow up with client",
        "due_date": "2026-06-07",
        "is_read": false
      }
    ]
    ```

---

## 10. Public Signed Client Portal

These routes do not require standard user login but require **signed signature authentication** generated by Laravel.

### Show Signed Estimate
**`GET /portal/estimates/{estimate}`**
*   **Query Parameters:**
    *   `signature`: Hash signature validation token
    *   `expires`: Expiration timestamp
*   **Response:** HTML View for client presentation.

### Accept Portal Estimate
**`POST /portal/estimates/{estimate}/accept`**
Client accepts the estimate and signs it.

*   **Request Body:**
    ```json
    {
      "client_name": "Oliver Twist",
      "signature_data": "data:image/png;base64,iVBORw0KGgo..."
    }
    ```
*   **Response (JSON):**
    ```json
    {
      "success": true,
      "message": "Estimate accepted successfully."
    }
    ```

### Decline Portal Estimate
**`POST /portal/estimates/{estimate}/decline`**
Client declines the estimate, indicating reasons.

*   **Request Body:**
    ```json
    {
      "reason": "budget",
      "comments": "The price exceeds our budget allocation."
    }
    ```
*   **Response (JSON):**
    ```json
    {
      "success": true,
      "message": "Estimate decline registered."
    }
    ```
