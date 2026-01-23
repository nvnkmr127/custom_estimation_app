<?php

namespace App\Livewire;

use App\Models\RoomTemplate;
use Livewire\Component;
use Livewire\WithPagination;

class RoomTemplateIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $viewMode = 'table';

    protected $queryString = [
        'search' => ['except' => ''],
        'viewMode' => ['except' => 'table'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function deleteTemplate($id)
    {
        $template = RoomTemplate::find($id);
        if ($template) {
            $template->delete();
            session()->flash('success', 'Template deleted successfully.');
        }
    }

    public function render()
    {
        $templates = RoomTemplate::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(12);

        return view('livewire.room-template-index', [
            'templates' => $templates,
        ]);
    }
}
