# Product Requirements Document (PRD): Custom Estimation App

## 1. Project Overview
The **Custom Estimation App** is a specialized web-based platform designed to streamline the creation, management, and approval of project estimates (e.g., construction, interior design, or similar industries). It enables collaboration between estimators, administrators, and clients by integrating product management, automation, and client communication into a unified workflow.

---

## 2. User Roles

- **Super Admin**
  - Full system access
  - System-wide settings, permissions, automation configuration, and user management

- **Estimator Admin**
  - Manage templates, products, and team-wide settings

- **Estimator**
  - Create estimates
  - Manage clients
  - Track project progress

- **Client**
  - View, approve, or decline estimates via a secure portal

---

## 3. Core Modules & Use Cases

### 3.1 Authentication & Profile

**Pages**
- `/login`
- `/register`
- `/password/reset`
- `/profile`

**Use Cases**
- Secure user registration and login
- Profile management (personal details, passwords, notification preferences)
- AI-generated professional bios/descriptions

---

### 3.2 Dashboard

**Page**
- `/dashboard`

**Use Cases**
- High-level overview (pending estimates, recent activity)
- Quick actions (create estimates or contacts)
- Notifications and task reminders

---

### 3.3 Estimate Management

**Pages**
- `/estimates`
- `/estimates/create`
- `/estimates/{id}/edit`
- `/estimates/{id}`

**Use Cases**
- Create new project estimates
- Add line items from product library or custom entries
- Room/section organization (e.g., Kitchen, Living Room)
- Automatic pricing calculations (subtotals, taxes, margins)
- Internal approval workflow
- Version control (V1, V2, etc.)
- Generate professional PDFs
- Email estimates to clients with secure links
- Internal team comments and collaboration

---

### 3.4 Client Portal

**Page**
- `/portal/estimates/{id}` (Signed Route)

**Use Cases**
- View estimate without login via unique link
- Accept or decline proposals digitally
- Client comments and change requests
- One-click follow-up call request

---

### 3.5 Product Library

**Pages**
- `/products`
- `/categories`

**Use Cases**
- Manage products/services with costs, prices, images, and descriptions
- Bulk import/export via CSV or Excel
- Product approval workflows
- Hierarchical categorization for easy retrieval

---

### 3.6 Client / Contact Management

**Pages**
- `/clients`

**Use Cases**
- Centralized client database
- Store contact details, interaction history, and linked estimates
- CRM integration (Perfex)

---

### 3.7 Templates & Resources

**Pages**
- `/templates` (Room Templates)
- `/email-templates`
- `/pdf-templates`

**Use Cases**
- Standardized estimate structure
- Faster estimate creation using predefined room templates
- Consistent email and PDF branding

---

### 3.8 Automation & Workflows

**Pages**
- `/admin/automation`
- `/admin/automation/experiments`

**Use Cases**
- Event-based automation (e.g., estimate viewed or accepted)
- Automated nurture and follow-up sequences
- Visual drag-and-drop workflow builder
- A/B testing for automation rules
- Automation performance analytics

---

### 3.9 Admin & Settings

**Pages**
- `/settings`
- `/users`
- `/permissions`
- `/approval-chains`

**Use Cases**
- Team onboarding and role assignment
- Margin rules, discount limits, approval thresholds
- Branding management (logos, colors, document styles)

---

### 3.10 Reporting & Analytics

**Pages**
- `/reports`
- `/estimates/{id}/analytics`

**Use Cases**
- Conversion rates, revenue pipeline, and team performance tracking
- Client engagement analytics (views, time spent)

---

## 4. Technical Integrations

- **Perfex CRM** – Two-way client and lead sync
- **Email (SMTP / Mailgun)** – Transactional and marketing emails
- **PDF Generator** – High-fidelity server-side PDF rendering
- **Pixel Tracking** – Track email opens and estimate views
