# PRD: Estimate Structure & Items

## 1. Overview
The **Structure & Items** module defines how the content of an estimate is organized. The system supports two distinct modes: a simple **Standard List** for quick quotes and a sophisticated **Room-Based** mode for complex projects (e.g., renovation, interior design).

## 2. User Stories
- **Room Organization**: As an estimator, I want to group items by "Room" (e.g., Kitchen, Master Bath) so the client understands the scope per area.
- **Quick Entry**: As an estimator, I want to add items quickly from a product library.
- **Custom Items**: As an estimator, I want to add one-off custom line items that aren't in the library.
- **Calculation**: As an estimator, I want the system to calculate layout quantity based on my measurements (L x W x H).

## 3. Functional Requirements

### 3.1 Organization Modes
- **Standard Mode**:
    - Flat list of items.
    - Use Case: Simple supply orders, flat-fee services.
- **Room-Based Mode**:
    - Hierarchy: `Estimate` -> `Sections` (Rooms) -> `Items`.
    - Features:
        - **Drag-and-Drop Rooms**: Reorder rooms significantly.
        - **Drag-and-Drop Items**: Move items between rooms.
        - **Room Subtotals**: UI displays calculated total cost per room.

### 3.2 Item Properties
Every line item (`estimate_items`) supports:
- **Product Link**: Optional FK to `products`. If linked, pulls default price/cost/image.
- **Image**: URL to product image (visible on PDF).
- **Unit Configuration**:
    - **Manual**: Free text unit (e.g., "Box").
    - **Strict Type**: Linked to `unit_types` (e.g., "sqft", "m2").
- **Costing**:
    - `unit_price`: Client-facing price.
    - `cost`: Internal cost (hidden from client).
    - `tax_1`, `tax_2`: Per-item tax rates (currently inherited globally, but schema supports per-item).

### 3.3 Area Calculator
A modal tool accessible per item to helper quantity calculation.
- **Inputs**: Length, Width, Height, Formula (L*W, L*W*H, etc.).
- **Output**: Automatically populates the `quantity` field.
- **Visuals**: Displays calculated "Area" coverage in the UI.

### 3.4 Product Picker
- **Search**: AJAX-driven search against `products` table.
- **Filtering**: Filter by Category.
- **Selection**: Selecting a product hydrates the item row with:
    - Name & Description
    - Default Price & Cost
    - Product Image
    - Default Unit Type

## 4. Technical Specifications
- **Tables**:
    - `estimate_sections` (id, estimate_id, name, order_index)
    - `estimate_items` (id, estimate_section_id, product_id, ...)
- **Frontend Tech**:
    - **Alpine.js**: Handles the reactive state of the items array `window.estimateData.items`.
    - **Sortable.js**: Handles the drag-and-drop DOM manipulation and updates the underlying array order.

### 4.1 Calculation Logic (`EstimateService`)
- The backend mirrors the frontend calculation to ensure integrity.
- **Subtotal**: `Sum(Item.unit_price * Item.quantity)`
- **Formula**: `Item.total = Round(Item.unit_price * Item.quantity, 2)`

## 5. UI/UX Requirements
- **Fluid Input**: Quantities and Prices should auto-recalculate row totals instantly (`@input` events).
- **Empty States**: Rooms with no items should show a clear "Add Item" CTA.
- **Visual Handles**: Clearly visible "grab handles" for drag-and-drop rows.
