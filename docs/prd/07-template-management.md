# PRD: Template Management System

## 1. Overview
The **Template Management System** is designed to maximize estimator efficiency and ensure brand consistency across all client touchpoints. By centralizing the structure of estimates, the content of emails, and the design of PDFs, the system reduces manual entry and minimizes errors.

## 2. User Stories
- **Speed**: As an estimator, I want to load a "Standard Bathroom" template so I don't have to add 20 items manually every time.
- **Consistency**: As a literal brand manager, I want to ensure every estimate PDF follows our latest brand guidelines without manual styling.
- **Bulk Scaling**: As an estimator, I want to add a "Premium Fixture Package" to any room with one click.
- **Communication**: As a user, I want to send standardized follow-up emails that automatically include the client's name and estimate total.

## 3. Functional Requirements

### 3.1 Room Templates
- **Definition**: A "blueprint" for an estimate section (e.g., "Full Kitchen Renovation").
- **Properties**:
    - **Name & Description**: Internal identifiers.
    - **Items (JSON)**: A pre-defined list of products/services.
    - **Allowed Unit Types**: Restrict which units can be used in this room (Optional).
- **Usage**: When applied in the Estimate Builder, it creates a new section and populates it with all template items, complete with default quantities and prices.

### 3.2 Item Packages
- **Definition**: A bundle of items that can be dropped into *any* existing room/section.
- **Features**:
    - Supports "Package Pricing" (Bulk discount applied to the bundle).
    - Can contain multiple items from different categories.
- **Usage**: Accessible via an "+ Add Package" button within any section in the builder.

### 3.3 Email Templates
- **Definition**: HTML/Text templates for transactional emails (Estimate Sent, Follow-up, Acceptance).
- **Features**:
    - **Dynamic Placeholders**: Support for `{{client_name}}`, `{{estimate_number}}`, `{{grand_total}}`, `{{portal_link}}`.
    - **Preview Mode**: Ability to see the rendered email with dummy data.

### 3.4 PDF Templates
- **Definition**: High-fidelity HTML/CSS templates for the final proposal document.
- **Features**:
    - **Branding**: Configurable primary/secondary colors and font families.
    - **Page Layout**: Paper size (A4/Letter) and orientation choice.
    - **Security**: Optional password protection and watermarking ("Draft").
    - **Versioning**: History of template changes (`PdfTemplateVersion`) to allow reverting to previous designs.

## 4. Technical Specifications
- **Models**:
    - `RoomTemplate` (Hierarchy: name, items_array)
    - `ItemPackage` (Hierarchy: name, total_price, items_array)
    - `EmailTemplate` (Hierarchy: name, type, body)
    - `PdfTemplate` (Hierarchy: name, html, css, settings)
- **Controllers**:
    - `RoomTemplateController`
    - `ItemPackageController`
    - `EmailTemplateController`
    - `PdfTemplateController`

### 4.1 Hydration Logic
- When a template is loaded, the system must "hydrate" the JSON data:
    1.  Look up `product_id` to ensure pricing/descriptions are current.
    2.  Merge template overrides (e.g., if a template specifies a custom quantity).
    3.  Hand off to the `EstimateService` for total recalculation.

## 5. UI/UX Requirements
- **Visual Previewer**: For PDF and Email templates, a side-by-side "Code vs Preview" editor.
- **Template Gallery**: A clean grid view where estimators can browse Room Templates with summaries of their contents.
- **Locking**: Ability to "Lock" a template so it cannot be modified by standard users, only by Admins.
