# PDF Template Variable Reference Guide

This document provides a complete list of all dynamic variables available for use in the PDF Template Editor.

## 🖱️ General Usage
- **Simple Variables**: Use `{variable_name}` to insert data.
- **Conditionals**: Use `{IF_variable_name}...{ENDIF}` to show or hide blocks of content.
- **Loops**: Use `{LOOP_ITEMS}...{END_LOOP}` for line items, and `{LOOP_SECTIONS}...{END_LOOP_SECTIONS}` for room-based grouping.

---

## 🛠️ Estimate Details
| Variable | Description | Example |
| :--- | :--- | :--- |
| `{estimate_number}` | The unique estimate number. | `EST-001` |
| `{estimate_title}` | Title or project name of the estimate. | `Kitchen Renovation` |
| `{estimate_date}` | Date the estimate was created. | `Feb 14, 2024` |
| `{expiry_date}` | Expiration date of the estimate. | `Mar 14, 2024` |
| `{status}` | Current status of the estimate. | `Accepted` |
| `{currency}` | Currency symbol/code. | `$` |

## 💰 Financials
| Variable | Description | Example |
| :--- | :--- | :--- |
| `{subtotal}` | Estimate subtotal (formatted). | `1,000.00` |
| `{discount_total}` | Total discount amount (formatted). | `50.00` |
| `{tax_total}` | Total tax amount (formatted). | `95.00` |
| `{grand_total}` | Final total amount (formatted). | `1,045.00` |
| `{transportation_charges}` | Shipping or transport charges. | `25.00` |
| `{dynamic_totals}` | **Recommended**: Complete formatted HTML totals table. | *(HTML Table)* |

## 👤 Client Info
| Variable | Description | Example |
| :--- | :--- | :--- |
| `{client_name}` | Full name of the client. | `John Doe` |
| `{client_email}` | Client email address. | `john@example.com` |
| `{client_phone}` | Client phone number. | `+1 555-0199` |
| `{client_address}` | Client street address. | `123 Main St` |

## 🏢 Company & Estimator
| Variable | Description | Example |
| :--- | :--- | :--- |
| `{company_name}` | Your company name. | `Acme Corp` |
| `{company_email}` | Your company email address. | `sales@acme.com` |
| `{company_address}` | Your company physical address. | `99 Innovation Dr` |
| `{company_logo}` | Company logo as a ready-to-use `<img>` tag. | `<img src="..." />` |
| `{estimator_name}` | Name of the person who created the estimate. | `Alice Agent` |
| `{estimator_email}` | Email of the estimator. | `alice@acme.com` |

## 📝 Notes & Terms
| Variable | Description | Example |
| :--- | :--- | :--- |
| `{client_note}` | Notes intended for the client. | `Thank you for the business!` |
| `{terms}` | Terms and conditions (HTML allowed). | `Net 30. No returns on labor.` |

---

## 🔄 Dynamic Loops

### 1. Flat Items Loop
Use this for simple estimates without room grouping.
```html
{LOOP_ITEMS}
  <p>{item_name}: {item_total}</p>
{END_LOOP}
```

### 2. Room/Section Based Loop
Use this for professional estimates grouped by sections/rooms.
```html
{LOOP_SECTIONS}
  <h3>{section_name} - {section_subtotal}</h3>
  {LOOP_ITEMS}
     <div>{item_name} x {item_quantity} = {item_total}</div>
  {END_LOOP}
{END_LOOP_SECTIONS}
```

---

## 📌 Item Context (Inside `LOOP_ITEMS`)
| Variable | Description | Example |
| :--- | :--- | :--- |
| `{item_name}` | Name of the line item. | `Wall Paint` |
| `{item_description}` | Item description. | `2 coats Eggshell` |
| `{item_image}` | Image of the item. | `<img ... />` |
| `{item_quantity}` | Quantity. | `5` |
| `{item_unit}` | Unit type (e.g., hrs, sqft). | `sqft` |
| `{item_price}` | Individual unit price. | `45.00` |
| `{item_total}` | Line total (including tax). | `225.00` |
| `{item_comments}` | Formatted list of item comments. | `<div>...</div>` |
| `{item_unit_configuration}`| Dimension/Formula info. | `Area (L:10 x W:20)` |

---

## 🚩 Logic Flags (Conditionals)
Control visibility with `{IF_variable}`...`{ENDIF}`.
- `has_discount`: Displays if a discount is present.
- `has_tax`: Displays if tax is applied.
- `has_transportation`: Displays if shipping is charged.
- `room_based`: Displays if the estimate has sections.

## 📊 Visual Charts
| Variable | Description |
| :--- | :--- |
| `{CHART_SECTIONS_PIE}` | Pie chart showing costs by Room/Section. |
| `{CHART_ITEMS_BAR}` | Bar chart showing top items by cost. |
| `{CHART_SECTIONS_BAR}` | Bar chart of section totals. |
| `{CHART_ROOMS}` | General room distribution chart. |
