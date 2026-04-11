<?php

namespace App\Livewire\Admin\Webhooks;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\WebhookConfig;
use Illuminate\Support\Str;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public $showEditModal = false;
    public $selectedWebhookId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function edit($id)
    {
        $this->selectedWebhookId = $id;
        $this->showEditModal = true;
    }

    public function closeModal()
    {
        $this->showEditModal = false;
        $this->selectedWebhookId = null;
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleStatus($id)
    {
        $webhook = WebhookConfig::findOrFail($id);
        $this->authorize('update', $webhook);
        $webhook->update([
            'status' => $webhook->status === 'active' ? 'inactive' : 'active'
        ]);
    }

    public function delete($id)
    {
        $webhook = WebhookConfig::findOrFail($id);
        $this->authorize('delete', $webhook);

        $webhook->delete();

        session()->flash('message', 'Webhook endpoint deleted successfully.');
    }

    public function render()
    {
        $query = WebhookConfig::query()
            ->with(['latestDelivery', 'dailyMetrics']);

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('url', 'like', '%' . $this->search . '%');
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->sortField === 'success_rate') {
            // Sorting by calculated attribute is hard in DB query.
            // For now, we sort by primary key or created_at if complex sort requested
            // Or implement a subquery if critical.
            // Fallback to name for simplicity in this iteration unless we add a computed column.
            // Let's stick to simple DB fields for sorting for now to avoid complexity.
            $query->orderBy('name', $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        return view('livewire.admin.webhooks.index', [
            'webhooks' => $query->paginate(10),
        ]);
    }
}
