# PRD: Estimate Output & Delivery

## 1. Overview
The final mile of the estimation process is presentation. This module covers the generation of the PDF document and the mechanisms for delivering it to the client (Email + Portal).

## 2. User Stories
- **Professionalism**: As a brand manager, I want our PDFs to look stunning, with high-res images and proper page breaks.
- **Tracking**: As a salesperson, I want to know when the client **views** the estimate.
- **Accessibility**: As a client, I want to view the quote on my phone without downloading a heavy PDF.

## 3. Functional Requirements

### 3.1 PDF Generation
- **Engine**: DomPDF (or Browsershot/Puppeteer if installed).
- **Templates**: Support for `PdfTemplate` records allowing dynamic HTML/CSS overrides.
- **Content**:
    - Header: Logo, Company Info.
    - Client Info: Bill To/Ship To.
    - Body: Room-Based sections or Standard list.
    - Footer: Terms & Conditions, Signature Block.
    - Images: Product images must be embedded/linked correctly.

### 3.2 Delivery Channels
1.  **Email**:
    - System sends a transactional email with a **Magic Link** (Signed URL).
    - PDF attachment (Optional toggle).
2.  **Client Portal**:
    - Route: `/portal/estimates/{id}`.
    - Features:
        - Web-view of the estimate (HTML).
        - "Download PDF" button.
        - "Accept" / "Decline" actions.
        - Chat/Comment thread.

### 3.3 Tracking & Analytics
- **View Tracking**: Use a 1x1 tracking pixel in the email or fire an event on Portal Page Load.
- **Metrics**: Track `view_count`, `last_viewed_at`, `email_opened_at`.

## 4. Technical Specifications
- **Service**: `PdfRenderingService`
- **Event**: `EstimateSent`
- **Route**: `portal.show` (Signed Middleware required).

## 5. UI/UX Requirements
- **Preview**: "Preview PDF" button in Admin/Edit view to check layout before sending.
- **Mobile Responsive**: The Portal web-view must be fully responsive.
- **Success State**: When a client accepts, show a celebration animation/confetti and clearly instruct text steps (e.g., "An invoice will be sent shortly").
