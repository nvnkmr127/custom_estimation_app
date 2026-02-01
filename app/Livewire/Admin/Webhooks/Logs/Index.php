<?php

namespace App\Livewire\Admin\Webhooks\Logs;

use App\Models\WebhookConfig;
use App\Models\WebhookDelivery;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;

class Index extends Component
{
    use WithPagination;

    public $search = ''; // Search trace_id, event_id, error message
    public $status = '';
    public $webhookId = '';
    public $dateRange = '7d'; // 24h, 7d, 30d

    public $expandedRows = [];

    protected $queryString = ['search', 'status', 'webhookId', 'dateRange'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleRow($id)
    {
        if (in_array($id, $this->expandedRows)) {
            $this->expandedRows = array_diff($this->expandedRows, [$id]);
        } else {
            $this->expandedRows[] = $id;
        }
    }

    public function render()
    {
        $query = WebhookDelivery::query()
            ->with(['webhook', 'event']);

        // Search
        if ($this->search) {
            $query->where(function (Builder $q) {
                $q->where('id', 'like', "%{$this->search}%")
                    ->orWhere('error_message', 'like', "%{$this->search}%")
                    ->orWhereHas('event', function (Builder $sq) {
                        $sq->where('id', 'like', "%{$this->search}%")
                            ->orWhere('event_type', 'like', "%{$this->search}%")
                            // Primitive JSON search for trace_id
                            ->orWhere('payload', 'like', "%{$this->search}%");
                    });
            });
        }

        // Status Filter
        if ($this->status) {
            $query->where('status', $this->status);
        }

        // Endpoint Filter
        if ($this->webhookId) {
            $query->where('webhook_id', $this->webhookId);
        }

        // Date Range
        $cutoff = match ($this->dateRange) {
            '24h' => now()->subDay(),
            '30d' => now()->subDays(30),
            default => now()->subDays(7),
        };
        $query->where('created_at', '>=', $cutoff);

        $logs = $query->latest()->paginate(20);
        $webhooks = WebhookConfig::orderBy('name')->get();

        return view('livewire.admin.webhooks.logs.index', [
            'logs' => $logs,
            'webhooks' => $webhooks
        ]);
    }
}
