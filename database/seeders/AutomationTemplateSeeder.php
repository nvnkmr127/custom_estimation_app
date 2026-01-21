<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AutomationTemplate;

class AutomationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Welcome Email
        AutomationTemplate::create([
            'name' => 'New Client Welcome Email',
            'description' => 'Send a welcome email when a new client is added.',
            'category' => 'Onboarding',
            'is_system' => true,
            'structure' => [
                'name' => 'Client Welcome Sequence',
                'description' => 'Automatically welcomes new clients via email.',
                'condition_logic' => 'AND',
                'settings' => [],
                'triggers' => [
                    [
                        'event_name' => 'client.created',
                        'provider' => 'system',
                        'config' => []
                    ]
                ],
                'global_conditions' => [],
                'steps' => [
                    [
                        'name' => 'Wait 1 Hour',
                        'description' => 'Gentle delay before sending.',
                        'order' => 1,
                        'delay' => 3600,
                        'retry_limit' => 0,
                        'on_failure' => 'continue',
                        'is_enabled' => true,
                        'conditions' => []
                    ],
                    [
                        'name' => 'Send Welcome Email',
                        'description' => 'Sends the welcome email template.',
                        'order' => 2,
                        'delay' => 0,
                        'retry_limit' => 3,
                        'on_failure' => 'stop',
                        'is_enabled' => true,
                        'action' => [
                            'type' => 'email',
                            'config' => [
                                'template_id' => null, // User would pick one
                                'subject' => 'Welcome to Our Service!',
                                'body' => 'Hi {{client.name}}, welcome aboard!',
                                'to' => '{{client.email}}'
                            ]
                        ],
                        'conditions' => []
                    ]
                ],
                'schedules' => []
            ]
        ]);

        // 2. High Value Estimate Alert
        AutomationTemplate::create([
            'name' => 'High Value Estimate Alert',
            'description' => 'Notify admins when an estimate over $5,000 is created.',
            'category' => 'Sales',
            'is_system' => true,
            'structure' => [
                'name' => 'High Value Alert > $5k',
                'description' => 'Internal notification for big potential deals.',
                'condition_logic' => 'AND',
                'settings' => [],
                'triggers' => [
                    [
                        'event_name' => 'estimate.created',
                        'provider' => 'system',
                        'config' => []
                    ]
                ],
                'global_conditions' => [
                    [
                        'type' => 'payload',
                        'field' => 'total',
                        'operator' => '>',
                        'value' => '5000'
                    ]
                ],
                'steps' => [
                    [
                        'name' => 'Notify Sales Team',
                        'description' => 'Sends internal notification.',
                        'order' => 1,
                        'delay' => 0,
                        'retry_limit' => 0,
                        'on_failure' => 'continue',
                        'is_enabled' => true,
                        'action' => [
                            'type' => 'notification',
                            'config' => [
                                'message' => 'New high value estimate created: #{{estimate.number}} for ${{estimate.total}}',
                                'channels' => ['database']
                            ]
                        ],
                        'conditions' => []
                    ]
                ],
                'schedules' => []
            ]
        ]);

        // 3. Estimate Follow-Up
        AutomationTemplate::create([
            'name' => 'Estimate Follow-Up Sequence',
            'description' => 'Follow up if estimate is not accepted after 3 days.',
            'category' => 'Sales',
            'is_system' => true,
            'structure' => [
                'name' => 'Standard Follow-Up',
                'description' => 'Auto-nudge clients who have not responded.',
                'condition_logic' => 'AND',
                'settings' => [],
                'triggers' => [
                    [
                        'event_name' => 'estimate.sent',
                        'provider' => 'system',
                        'config' => []
                    ]
                ],
                'global_conditions' => [],
                'steps' => [
                    [
                        'name' => 'Wait 3 Days',
                        'description' => 'Wait for client to view/act.',
                        'order' => 1,
                        'delay' => 259200, // 3 days
                        'retry_limit' => 0,
                        'on_failure' => 'continue',
                        'is_enabled' => true,
                        'conditions' => []
                    ],
                    [
                        'name' => 'Check Status',
                        'description' => 'Only proceed if still sent (not accepted/declined).',
                        'order' => 2,
                        'delay' => 0,
                        'retry_limit' => 0,
                        'on_failure' => 'stop',
                        'is_enabled' => true,
                        'conditions' => [
                            [
                                'type' => 'entity',
                                'field' => 'status',
                                'operator' => '=',
                                'value' => 'sent'
                            ]
                        ]
                    ],
                    [
                        'name' => 'Send Reminder Email',
                        'description' => 'Gentle reminder.',
                        'order' => 3,
                        'delay' => 0,
                        'retry_limit' => 3,
                        'on_failure' => 'continue',
                        'is_enabled' => true,
                        'action' => [
                            'type' => 'email',
                            'config' => [
                                'subject' => 'Regarding your estimate #{{estimate.number}}',
                                'body' => 'Hi {{client.name}}, just checking in on the estimate we sent...',
                                'to' => '{{client.email}}'
                            ]
                        ],
                        'conditions' => []
                    ]
                ],
                'schedules' => []
            ]
        ]);

        // 4. Client Feedback Request
        AutomationTemplate::create([
            'name' => 'Client Feedback Request',
            'description' => 'Ask for a review 7 days after an estimate is accepted.',
            'category' => 'Customer Success',
            'is_system' => true,
            'structure' => [
                'name' => 'Post-Project Feedback',
                'description' => 'Automated review request sequence.',
                'condition_logic' => 'AND',
                'settings' => [],
                'triggers' => [
                    [
                        'event_name' => 'estimate.accepted',
                        'provider' => 'system',
                        'config' => []
                    ]
                ],
                'global_conditions' => [],
                'steps' => [
                    [
                        'name' => 'Wait 1 Week',
                        'description' => 'Allow time for work to be completed.',
                        'order' => 1,
                        'delay' => 604800, // 7 days
                        'retry_limit' => 0,
                        'on_failure' => 'continue',
                        'is_enabled' => true,
                        'conditions' => []
                    ],
                    [
                        'name' => 'Send Review Request',
                        'description' => 'Asks client for feedback.',
                        'order' => 2,
                        'delay' => 0,
                        'retry_limit' => 3,
                        'on_failure' => 'stop',
                        'is_enabled' => true,
                        'action' => [
                            'type' => 'email',
                            'config' => [
                                'subject' => 'How did we do on estimate #{{estimate.number}}?',
                                'body' => 'Hi {{client.name}}, we would love to hear your feedback...',
                                'to' => '{{client.email}}'
                            ]
                        ],
                        'conditions' => []
                    ]
                ],
                'schedules' => []
            ]
        ]);

        // 5. Team Notification (Deal Lost)
        AutomationTemplate::create([
            'name' => 'Deal Lost Alert',
            'description' => 'Notify sales manager when an estimate is declined.',
            'category' => 'Sales',
            'is_system' => true,
            'structure' => [
                'name' => 'Declined Estimate Alert',
                'description' => 'Internal alert for lost deals.',
                'condition_logic' => 'AND',
                'settings' => [],
                'triggers' => [
                    [
                        'event_name' => 'estimate.declined',
                        'provider' => 'system',
                        'config' => []
                    ]
                ],
                'global_conditions' => [],
                'steps' => [
                    [
                        'name' => 'Notify Manager',
                        'description' => 'Sends internal notification.',
                        'order' => 1,
                        'delay' => 0,
                        'retry_limit' => 0,
                        'on_failure' => 'continue',
                        'is_enabled' => true,
                        'action' => [
                            'type' => 'notification',
                            'config' => [
                                'message' => 'Estimate #{{estimate.number}} was declined. Reason: {{estimate.decline_reason}}',
                                'channels' => ['database', 'email']
                            ]
                        ],
                        'conditions' => []
                    ]
                ],
                'schedules' => []
            ]
        ]);

        // 6. Stale Estimate Alert (14 Days)
        AutomationTemplate::create([
            'name' => 'Stale Estimate Alert',
            'description' => 'Flag estimates that have been open for 14 days without action.',
            'category' => 'Sales',
            'is_system' => true,
            'structure' => [
                'name' => 'Stale Estimate Monitor',
                'description' => 'Checks for inaction on sent estimates.',
                'condition_logic' => 'AND',
                'settings' => [],
                'triggers' => [
                    [
                        'event_name' => 'estimate.sent',
                        'provider' => 'system',
                        'config' => []
                    ]
                ],
                'global_conditions' => [],
                'steps' => [
                    [
                        'name' => 'Wait 14 Days',
                        'description' => 'Wait two weeks.',
                        'order' => 1,
                        'delay' => 1209600, // 14 days
                        'retry_limit' => 0,
                        'on_failure' => 'continue',
                        'is_enabled' => true,
                        'conditions' => []
                    ],
                    [
                        'name' => 'Check if Still Pending',
                        'description' => 'Verify status is still sent.',
                        'order' => 2,
                        'delay' => 0,
                        'retry_limit' => 0,
                        'on_failure' => 'stop',
                        'is_enabled' => true,
                        'conditions' => [
                            [
                                'type' => 'entity',
                                'field' => 'status',
                                'operator' => '=',
                                'value' => 'sent'
                            ]
                        ]
                    ],
                    [
                        'name' => 'Notify Sales Rep',
                        'description' => 'Internal nudge to follow up.',
                        'order' => 3,
                        'delay' => 0,
                        'retry_limit' => 0,
                        'on_failure' => 'continue',
                        'is_enabled' => true,
                        'action' => [
                            'type' => 'notification',
                            'config' => [
                                'message' => 'Estimate #{{estimate.number}} has been pending for 14 days. Please follow up.',
                                'channels' => ['database']
                            ]
                        ],
                        'conditions' => []
                    ]
                ],
                'schedules' => []
            ]
        ]);
    }
}
