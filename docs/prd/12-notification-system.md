# Product Requirements Document (PRD): Notification System

## 1. Document Overview
*   **Module**: Notification & Communication Engine
*   **Status**: Active / Implementation Documented
*   **Version**: 1.0

The Notification System is a core horizontal service designed to facilitate seamless communication between the platform, its users, and external clients. It ensures that stakeholders are informed of critical events in real-time while providing granular control over the volume and frequency of communications.

---

## 2. Product Vision
To provide a reliable, scalable, and user-centric notification engine that enhances collaboration through timely updates, strengthens client engagement via tracked communications, and integrates easily with external workflows.

---

## 3. Core Capabilities

### 3.1 Event-Driven Core
The system architecture is entirely event-driven, leveraging specialized `DomainEvent` classes. This ensures loose coupling between business logic (e.g., creating an estimate) and notification logic (e.g., sending an email).

*   **Asynchronous Processing**: All notifications are dispatched via Laravel Queues to ensure zero impact on user-perceived performance.
*   **Retry Logic**: Automated retries for failed email deliveries and webhook timeouts.

### 3.2 Multi-Channel Support
The engine supports three primary delivery channels, each optimized for different use cases:

#### A. Email (Primary)
*   **Instant Notifications**: For time-sensitive actions like "Approval Requested" or "Estimate Sent".
*   **Digest Notifications**: Aggregated updates (Daily/Weekly) for non-urgent events like "Estimate Viewed" or "Follower Added".
*   **Tracking & Engagement**: 
    *   **Open Tracking**: Invisible pixel tracking to monitor when recipients open emails.
    *   **Click Tracking**: Automatic URL rewriting to monitor engagement with links.
    *   **Logging**: Full historical log of all outgoing communications via `EmailLog`.

#### B. Webhooks (Integration)
*   **Outbound Integration**: Send JSON payloads to third-party URLs (e.g., Zapier, CRM, Custom Apps).
*   **Configurable Hooks**: Currently supports `estimate.submitted_for_approval` and `estimate.sent`.

#### C. In-App Notifications (Engagement)
*   **Persistence**: Notifications stored in the database for later viewing within the app.
*   **Real-time (Planned)**: WebSocket integration for instant UI updates.

---

## 4. Feature Specifications

### 4.1 Granular Notification Preferences
Users can manage how they receive updates via the `notification_preferences` table.
*   **Frequency Levels**:
    *   `Instant`: Sent immediately upon event occurrence.
    *   `Daily/Weekly Digest`: Batched into a single summary email.
    *   `Muted`: Notification is suppressed.
*   **Default Behavior**: Critical system events (Registrations, Approvals) default to `Instant`.

### 4.2 Dynamic Templating System
Powered by the `TemplateService`, notifications are both flexible and brand-aware.
*   **Hybrid Storage**: Templates can be loaded from standard Blade files or customized within the database (`email_templates` table).
*   **Brand Injection**: Automatic injection of company logos, colors, and legal footers into every notification.
*   **Blade Parsing**: Support for full Blade syntax within database-stored templates.

### 4.3 Email Digest Engine
Managed by `SendDigestsCommand`, this component:
1.  Identifies users due for a digest.
2.  Aggregates events from the `pending_notifications` table.
3.  Sends a single, clean summary email, reducing inbox fatigue.

---

## 5. Technical Architecture

### 5.1 Key Components
| Component | Responsibility |
| :--- | :--- |
| **DomainEvent** | The "Signal" - captures data about an event (e.g., `EstimateSent`). |
| **Listeners** | The "Orchestrators" - `MailListener`, `WebhookListener` decide what to do with a signal. |
| **EmailDispatcher** | The "Delivery Agent" - handles rendering, tracking setup, and final queueing. |
| **PreferenceService** | The "Filter" - checks if a user actually wants the notification and at what frequency. |
| **TemplateService** | The "Artist" - composes the final message HTML from various sources. |

### 5.2 Database Schema
*   `notifications`: Standard storage for in-app history.
*   `notification_preferences`: User-specific settings per event type and channel.
*   `pending_notifications`: Queue for events waiting to be included in the next digest.
*   `email_logs`: Audit trail for all sent emails including status and tracking IDs.

---

## 6. Security & Reliability
*   **Webhook Signing (Recommended)**: Future support for HMAC signatures to allow recipients to verify webhook authenticity.
*   **Rate Limiting**: Protection against notification flood if an event is triggered repeatedly in a short duration.
*   **Data Masking**: Ensuring sensitive financial data is only included in notifications when necessary.

---

## 7. Future Roadmap
1.  **SMS Channel**: Integration with Twilio for mobile-first alerts.
2.  **Push Notifications**: Browser and Mobile push via Firebase/OneSignal.
3.  **Advanced Sequences**: Ability to trigger a series of notifications over time (e.g., Follow-up on an estimate after 3 days).
4.  **Analytics Dashboard**: Visual reports on email open rates and webhook success rates.
