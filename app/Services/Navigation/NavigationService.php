<?php

namespace App\Services\Navigation;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class NavigationService
{
    public function getSidebarMenu(): array
    {
        $user = User::query()->find(Auth::id());
        if (!$user) return [];

        $isAdmin = $user->isAdmin();
        $isSuperAdmin = $user->hasRole('super_admin');

        $menu = [];

        // 1. Overview
        $menu[] = [
            'group' => 'Overview',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'dashboard',
                    'icon' => 'heroicon-o-home',
                    'active' => Request::routeIs('dashboard'),
                ],
                [
                    'label' => 'Calendar',
                    'route' => 'calendar.index',
                    'icon' => 'heroicon-o-calendar',
                    'active' => Request::routeIs('calendar.*'),
                ],
                [
                    'label' => 'Approvals',
                    'route' => 'approvals.index',
                    'icon' => 'heroicon-o-check-badge',
                    'active' => Request::routeIs('approvals.*'),
                    'badge' => $this->getPendingApprovalsCount(),
                ],
                [
                    'label' => 'Reports',
                    'route' => 'reports.index',
                    'icon' => 'heroicon-o-chart-pie',
                    'active' => Request::routeIs('reports.*'),
                ],
            ]
        ];

        // 2. Operations
        $opsItems = [
            [
                'label' => 'Estimates',
                'route' => 'estimates.index',
                'icon' => 'heroicon-o-document-duplicate',
                'active' => Request::routeIs('estimates.*'),
            ],
            [
                'label' => 'Clients',
                'route' => 'clients.index',
                'icon' => 'heroicon-o-users',
                'active' => Request::routeIs('clients.*'),
            ],
        ];

        if ($isAdmin) {
            $opsItems[] = [
                'label' => 'Tasks',
                'route' => 'tasks.index',
                'icon' => 'heroicon-o-clipboard-document-list',
                'active' => Request::routeIs('tasks.*'),
            ];
            $opsItems[] = [
                'label' => 'Reminders',
                'route' => 'reminders.index',
                'icon' => 'heroicon-o-bell',
                'active' => Request::routeIs('reminders.*'),
            ];
        }

        $menu[] = [
            'group' => 'Operations',
            'items' => $opsItems
        ];

        // 3. Catalog (Admin Only)
        if ($isAdmin) {
            $menu[] = [
                'group' => 'Catalog',
                'items' => [
                    [
                        'label' => 'Brands',
                        'route' => 'brands.index',
                        'icon' => 'heroicon-o-tag',
                        'active' => Request::routeIs('brands.*'),
                    ],
                    [
                        'label' => 'Products Library',
                        'route' => 'products.index',
                        'icon' => 'heroicon-o-archive-box',
                        'active' => Request::routeIs('products.*'),
                        'badge' => \App\Models\Product::pending()->count(),
                        'badge_color' => 'amber',
                    ],
                    [
                        'label' => 'Categories',
                        'route' => 'categories.index',
                        'icon' => 'heroicon-o-swatch',
                        'active' => Request::routeIs('categories.*'),
                    ],
                    [
                        'label' => 'Unit Types',
                        'route' => 'unit-types.index',
                        'icon' => 'heroicon-o-adjustments-horizontal',
                        'active' => Request::routeIs('unit-types.*'),
                    ],
                    [
                        'label' => 'Room Templates',
                        'route' => 'templates.index',
                        'icon' => 'heroicon-o-square-3-stack-3d',
                        'active' => Request::routeIs('templates.*'),
                    ],
                    [
                        'label' => 'PDF Templates',
                        'route' => 'pdf-templates.index',
                        'icon' => 'heroicon-o-document-text',
                        'active' => Request::routeIs('pdf-templates.*'),
                    ],
                    [
                        'label' => 'Email Templates',
                        'route' => 'email-templates.index',
                        'icon' => 'heroicon-o-envelope',
                        'active' => Request::routeIs('email-templates.*'),
                    ],
                    [
                        'label' => 'Packages',
                        'route' => 'packages.index',
                        'icon' => 'heroicon-o-cube',
                        'active' => Request::routeIs('packages.*'),
                    ],
                    [
                        'label' => 'Coupons',
                        'route' => 'coupons.index',
                        'icon' => 'heroicon-o-ticket',
                        'active' => Request::routeIs('coupons.*'),
                    ],
                ]
            ];

            // 4. Users & Access
            $accessItems = [
                [
                    'label' => 'User Directory',
                    'route' => 'users.index',
                    'icon' => 'heroicon-o-user-group',
                    'active' => Request::routeIs('users.*'),
                ]
            ];

            if ($isSuperAdmin) {
                $accessItems[] = [
                    'label' => 'Capability Matrix',
                    'route' => 'permissions.index',
                    'icon' => 'heroicon-o-shield-check',
                    'active' => Request::routeIs('permissions.*'),
                ];
                $accessItems[] = [
                    'label' => 'Approval Flows',
                    'route' => 'approval-chains.index',
                    'icon' => 'heroicon-o-arrow-path',
                    'active' => Request::routeIs('approval-chains.*'),
                ];
                $accessItems[] = [
                    'label' => 'Approval Checklists',
                    'route' => 'approval-checklists.index',
                    'icon' => 'heroicon-o-list-bullet',
                    'active' => Request::routeIs('approval-checklists.*'),
                ];
                $accessItems[] = [
                    'label' => 'Revision Checklists',
                    'route' => 'change-request-checklists.index',
                    'icon' => 'heroicon-o-clipboard-document-check',
                    'active' => Request::routeIs('change-request-checklists.*'),
                ];
            }

            $menu[] = [
                'group' => 'Users & Access',
                'items' => $accessItems
            ];

            // 5. Audits & Webhooks
            $auditItems = [
                [
                    'label' => 'System Events',
                    'route' => 'event-logs.index',
                    'icon' => 'heroicon-o-clock',
                    'active' => Request::routeIs('event-logs.*'),
                ],
            ];

            if ($isSuperAdmin) {
                $auditItems[] = [
                    'label' => 'Inbound Catchers',
                    'route' => 'admin.webhooks.catchers.index',
                    'icon' => 'heroicon-o-cloud-arrow-down',
                    'active' => Request::routeIs('admin.webhooks.catchers.*'),
                ];
                $auditItems[] = [
                    'label' => 'Outbound Endpoints',
                    'route' => 'admin.webhooks.index',
                    'icon' => 'heroicon-o-cloud-arrow-up',
                    'active' => Request::routeIs('admin.webhooks.index') && !Request::is('*/catchers*') && !Request::is('*/events*') && !Request::is('*/logs*') && !Request::is('*/dlq*'),
                ];
                $auditItems[] = [
                    'label' => 'Payload Explorer',
                    'route' => 'admin.webhooks.events.index',
                    'icon' => 'heroicon-o-magnifying-glass-circle',
                    'active' => Request::routeIs('admin.webhooks.events.*'),
                ];
                $auditItems[] = [
                    'label' => 'Delivery Logs',
                    'route' => 'admin.webhooks.logs.index',
                    'icon' => 'heroicon-o-document-magnifying-glass',
                    'active' => Request::routeIs('admin.webhooks.logs.*'),
                ];
                $auditItems[] = [
                    'label' => 'Dead Letter Queue',
                    'route' => 'admin.webhooks.dlq.index',
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'active' => Request::routeIs('admin.webhooks.dlq.*'),
                    'badge' => \App\Models\WebhookInboundEvent::where('status', 'failed')->count(),
                    'badge_color' => 'rose',
                ];
            }

            $menu[] = [
                'group' => 'Audits & Webhooks',
                'items' => $auditItems
            ];

            // 6. System Logic
            $menu[] = [
                'group' => 'System Logic',
                'items' => [
                    [
                        'label' => 'Automation Engine',
                        'route' => 'automation.index',
                        'icon' => 'heroicon-o-bolt',
                        'active' => Request::routeIs('automation.*') && Request::get('open_templates') !== '1',
                    ],
                    [
                        'label' => 'Automation Templates',
                        'route' => 'automation.index',
                        'params' => [
                            'open_templates' => 1,
                        ],
                        'icon' => 'heroicon-o-square-2-stack',
                        'active' => Request::routeIs('automation.*') && Request::get('open_templates') === '1',
                    ],
                    [
                        'label' => 'Nurture Rules',
                        'route' => 'settings.nurture',
                        'icon' => 'heroicon-o-fire',
                        'active' => Request::routeIs('settings.nurture'),
                    ],
                    [
                        'label' => 'Plugins Manager',
                        'route' => 'admin.plugins.index',
                        'icon' => 'heroicon-o-puzzle-piece',
                        'active' => Request::routeIs('admin.plugins.*'),
                        'role' => ['super_admin', 'estimator_admin'],
                    ],
                    [
                        'label' => 'Global Settings',
                        'route' => 'settings.edit',
                        'icon' => 'heroicon-o-cog-6-tooth',
                        'active' => Request::routeIs('settings.edit'),
                    ],
                    [
                        'label' => 'Backup & Restore',
                        'route' => 'backup.index',
                        'icon' => 'heroicon-o-shield-exclamation',
                        'active' => Request::routeIs('backup.*'),
                        'role' => 'super_admin'
                    ],
                ]
            ];
        }

        // 7. Support
        $menu[] = [
            'group' => 'Support',
            'items' => [
                [
                    'label' => 'User Guide',
                    'route' => 'guide.index',
                    'icon' => 'heroicon-o-book-open',
                    'active' => Request::routeIs('guide.*'),
                    'target' => '_blank',
                ],
            ]
        ];

        return $menu;
    }

    private function getPendingApprovalsCount(): int
    {
        return \App\Models\Estimate::where('estimate_status', \App\Models\Estimate::EST_STATUS_PENDING_APPROVAL)
            ->whereHas('approvals', function ($q) {
                $q->where('user_id', Auth::id())
                    ->where('status', 'pending');
            })
            ->count();
    }
}
