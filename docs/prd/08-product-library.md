# PRD: Product Library & Category Management

## 1. Overview
The **Product Library** is the foundational database for all estimates. It provides a centralized, curated list of products and services, ensuring that all estimators use consistent naming, SKU numbering, and baseline pricing.

## 2. User Stories
- **Scalability**: As an admin, I want to bulk-import 500 products from a spreadsheet so I don't have to enter them manually.
- **Variant Tracking**: As an estimator, I want to choose between "Matte" or "Gloss" finish for a product so the quote is technically accurate.
- **Quality Control**: As a super admin, I want to review and approve product suggestions made by junior estimators before they become available system-wide.
- **Lifecycle Management**: As a manager, I want to "Retire" old products while keeping historical estimates that used them intact.

## 3. Functional Requirements

### 3.1 Product Structure
- **Core Identifiers**: Name, Description, SKU, Category.
- **Financial Details**: Baseline `unit_price`, internal `cost`, and optional per-item tax overrides (`tax_1`, `tax_2`).
- **Media**: Multiple images per product to show clients what they are buying.
- **Attributes (JSON)**: Flexible key-value pairs for technical specs (e.g., "Weight: 10kg", "Material: Oak").
- **Options**: Support for variants (e.g., Color, Size) through the `ProductOption` and `ProductOptionValue` models.

### 3.2 Dynamic Categorization
- **Hierarchy**: Products belong to a `ProductCategory`.
- **Filtering**: The Estimate Builder uses these categories to allow fast filtering (e.g., "Show only Plumbing fixtures").

### 3.3 Status Workflow
- **Pending**: New products added by estimators that require review.
- **Active**: Available for use in new estimates.
- **Retired**: Hidden from search but preserved for historical documentation. Requires a `retirement_reason`.

### 3.4 Import/Export
- **CSV/Excel Sync**: Bulk upload functionality handles category mapping and image URL processing.

## 4. Technical Specifications
- **Models**:
    - `Product` (Main record)
    - `ProductCategory` (Hierarchical grouping)
    - `ProductImage` (Media relations)
    - `ProductOption` / `ProductOptionValue` (Variants)
- **Controller**: `ProductController`
- **Scopes**: `active()`, `retired()`, `pending()`, `search($term)`.

## 5. UI/UX Requirements
- **Product Gallery**: A visual "Catalog" view for browsing.
- **Image Uploader**: Drag-and-drop support for product photography.
- **Search-as-you-type**: High-performance search in the CRUD interface to handle thousands of records.
