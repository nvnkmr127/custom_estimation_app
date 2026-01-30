# Product Requirements Document (PRD): Automation & Workflow Engine

## 1. Document Overview
*   **Module**: Automation & Event-Driven Workflows
*   **Status**: Active / Core Implementation Complete
*   **Version**: 1.1

The Automation Engine is the "intelligence" layer of the Custom Estimation App. It enables business logic to be decoupled into configurable rules that react to system events, allowing for automated follow-ups, internal task creation, and dynamic status management without manual intervention.

---

## 2. Product Vision
To empower users to build "Self-Driving" business processes. By automating the repetitive "If-This-Then-That" logic of sales and project management, the platform ensures consistent client engagement and reduces administrative overhead.

---

## 3. The Core Logic Model: "Triggers, Conditions, Actions"

### 3.1 Triggers (The Entry Points)
Automations are initiated by `DomainEvents`. The engine listens across the entire application lifecycle.
*   **Life-Cycle Events**: `EstimateCreated`, `EstimateSent`, `EstimateViewed`, `EstimateAccepted`.
*   **User Interactions**: `CommentAdded`, `ReviewRequested`.
*   **System Events**: `UserRegistered`, `ApplicationAccessGranted`.

### 3.2 Advanced Condition Engine
The engine evaluates whether an automation should proceed based on deep-data inspection:
*   **Payload Inspection**: Check data within the event itself (e.g., `If total_amount > 5000`).
*   **Entity State**: Dynamically fetch and inspect the related model (e.g., `If Estimate Client has tag 'VIP'`).
*   **Frequency Analysis**: Check historical counts (e.g., `If this is the 3rd time the client has viewed the estimate`).
*   **Contextual Logic**: Support for `AND` / `OR` logic gates at both the Global (Workflow) and Step level.
*   **Temporal Filters**: Restrict runs by Time-of-Day or Day-of-Week (e.g., "Only send emails during business hours").

### 3.3 Dynamic Step Execution
Workflows consist of ordered `AutomationSteps` that can be executed:
*   **Synchronously**: Immediate action.
*   **Asynchronously (Delayed)**: Schedule actions for the future (e.g., "Wait 48 hours then send follow-up").
*   **Failure Handling**: Configurable `on_failure` policies (e.g., `halt` the entire trace if a step fails).

---

## 4. Supported Action Types
1.  **Email**: Send templated messages with dynamic data injection.
2.  **Webhook**: Forward event data to external systems for deep integration.
3.  **Internal Notification**: Create dashboard alerts and push notifications for team members.
4.  **Status Update**: Automatically manipulate model states (e.g., "If declined, change status to 'Archived'").

---

## 5. Enterprise Features

### 5.1 Experimentation (A/B Testing)
Built-in support for A/B testing via `ExperimentService`.
*   **Traffic Splitting**: Randomly assign events to different workflow variants.
*   **Conversion Tracking**: Measure which automation variant performs better against a defined goal.

### 5.2 Versioning & Concurrency
*   **Immutability**: Once an automation is triggered (a "Trace"), it follows the version of the workflow that existed at the time of the event.
*   **Current-Version Control**: Admins can iterate on logic in draft mode and "Publish" new versions without breaking "In-Flight" executions.

### 5.3 Safety & Governance
To prevent "Automation Meltdowns," the engine implements several safeguards:
*   **Loop Detection**: Automatic kill-switch if a workflow triggers itself or cycles rapidly (>50 executions/min).
*   **Rate Limiting**: Configurable quotas per workflow (e.g., "Max 500 emails per day").
*   **Entity Protection**: Limit how many times a specific estimate or client can trigger a specific automation.
*   **Payload Masking**: Automatically masks sensitive fields (`api_keys`, `tokens`) in execution logs for security.

---

## 6. Technical Stack
*   **Service Layer**: `AutomationService` (Orchestrates evaluation and execution).
*   **Job Architecture**: `HandleAutomationAction` (Handles delayed and async steps via Redis/Sqs).
*   **Persistence**: 
    *   `automation_execution_logs`: Full audit trail of every decision and action.
    *   `automation_triggers`: Mapping events to workflows.
    *   `automation_steps`: The sequence and delay definitions.

---

## 7. Future Roadmap
1.  **Visual Flow Builder**: A React-Flow or Vue-Flow based canvas for visual workflow editing.
2.  **External Triggers**: Ability to start an automation via an incoming webhook.
3.  **Cross-App Workflows**: Automations that span multiple sub-applications (e.g., "When Estimate is Paid in App A, provision User in App B").
4.  **AI Optimization**: Suggesting condition adjustments based on successful conversion patterns.
