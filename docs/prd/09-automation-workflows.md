# PRD: Automation, Nurturing & Event Workflows

## 1. Overview
The **Automation System** allows the business to scale its client engagement without increasing headcount. By reacting to "Events" (e.g., an estimate being viewed) with "Actions" (e.g., sending a reminder email 2 days later), the app ensures no lead is forgotten.

## 2. User Stories
- **Lead Nurturing**: As a sales manager, I want to automatically email a client 48 hours after they open an estimate if they haven't accepted it yet.
- **Internal Alerts**: As an estimator, I want a notification on my dashboard the moment a client "Accepts" a proposal so I can start the project.
- **A/B Testing**: As a marketing user, I want to run two different follow-up email versions to see which leads to higher acceptance rates.
- **Workflow Visualization**: As an admin, I want to see a flowchart of my automation logic to ensure there are no dead-ends.

## 3. Functional Requirements

### 3.1 Components of an Automation
- **Triggers**: The "When" (e.g., `Estimate Viewed`, `Status Changed`, `Client Request Call`).
- **Conditions**: The "If" (e.g., `If Grand Total > $10,000` or `If Client Tag is "VIP"`).
- **Steps**: The "What" (e.g., `Send Email`, `Create Task`, `Wait 2 Days`, `Update Status`).
- **Schedules**: Define *when* the automation is allowed to run (e.g., "Only during business hours").

### 3.2 Key Events Supported
The system integrates with the `EventDispatcher` to listen for:
- `EstimateCreated`
- `EstimateSent` (Email fired)
- `EstimateViewed` (Portal opened)
- `EstimateAccepted` / `Declined`

### 3.3 Experimentation (A/B Testing)
- **Experiments**: Define a "Control" and "Variant" group for different steps in an automation.
- **Analytics**: Track conversion metrics per version to identify which workflow is more effective.

### 3.4 Versioning
- Automations support version control (`is_current_version`), allowing admins to iterate on workflows without breaking currently "In Flight" executions.

## 4. Technical Specifications
- **Models**:
    - `Automation` (The root definition)
    - `AutomationTrigger` (The entry points)
    - `AutomationStep` (The sequence of actions)
    - `AutomationCondition` (The logic filters)
    - `AutomationExecutionLog` (The audit trail)
- **Processor**: `AutomationService` (Found in `App\Services`)

## 5. UI/UX Requirements
- **Flowchart Builder**: A visual interface for dragging and dropping steps.
- **Execution Timeline**: A view for each estimate showing exactly which automation steps were fired and when.
- **Dashboard Metrics**: High-level stats on "Active Runs", "Completed Nurtures", and "Conversions attributed to Automation".
