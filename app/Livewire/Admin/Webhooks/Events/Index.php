<?php

namespace App\Livewire\Admin\Webhooks\Events;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\WebhookEvent;
use App\Models\WebhookDelivery;
use Illuminate\Database\Eloquent\Builder;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $type = '';
    public $status = '';
    public $dateFrom = '';
    public $dateTo = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'type' => ['except' => ''],
        'status' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function updatingType()
    {
        $this->resetPage();
    }
    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = WebhookEvent::query()
            ->with(['deliveries'])
            ->latest('occurred_at');

        if ($this->search) {
            // Basic search in payload or ID
            $query->where(function (Builder $q) {
                $q->where('id', 'like', '%' . $this->search . '%')
                    ->orWhere('payload', 'like', '%' . $this->search . '%'); // JSON search equivalent for sqlite/mysql basic
            });
        }

        if ($this->type) {
            $query->where('event_type', $this->type);
        }

        if ($this->dateFrom) {
            $query->whereDate('occurred_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('occurred_at', '<=', $this->dateTo);
        }

        // Status filtering requires iterating or subquery since it's an aggregate.
        // For performance on large sets, status should be a column. 
        // For MVP/Admin view, we fetch and then filter (pagination breaks) OR use subquery.
        // Simplifying: We won't filter by "aggregate status" in DB efficiently without a column.
        // We will optionally highlight them in UI, or if critical, add a whereHas.
        // Let's support filtering by "Has at least one failure" via subquery if requested.
        // For now, simple list.

        $events = $query->paginate(25);

        // Stats for the dashboard
        $stats = [
            'total' => WebhookEvent::count(),
            'failed' => WebhookDelivery::where('status', 'failed')->distinct('webhook_event_id')->count('webhook_event_id'),
            'processing' => WebhookDelivery::whereIn('status', ['processing', 'retrying', 'pending'])->distinct('webhook_event_id')->count('webhook_event_id'),
            'success_rate' => 0,
        ];

        $totalDeliveries = WebhookDelivery::count();
        if ($totalDeliveries > 0) {
            $successDeliveries = WebhookDelivery::where('status', 'success')->count();
            $stats['success_rate'] = round(($successDeliveries / $totalDeliveries) * 100, 1);
        }

        return view('livewire.admin.webhooks.events.index', [
            'events' => $events,
            'types' => WebhookEvent::distinct('event_type')->pluck('event_type'),
            'stats' => $stats,
        ]);
    }
}
