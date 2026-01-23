<?php

namespace App\Livewire\Brands;

use App\Models\Brand;
use Livewire\Component;
use Livewire\WithPagination;

class BrandManager extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $isEditing = false;
    public $confirmingDeletion = false;
    public $brandIdToDelete;

    // Form fields
    public $name;
    public $logo_url;
    public $primary_color = '#000000';
    public $secondary_color = '#ffffff';
    public $footer_text;
    public $support_email;
    public $website_url;
    public $is_default = false;
    public $brandId;

    protected $rules = [
        'name' => 'required|string|max:255',
        'logo_url' => 'nullable|url|max:2048',
        'primary_color' => 'required|string|max:7',
        'secondary_color' => 'nullable|string|max:7',
        'footer_text' => 'nullable|string|max:1000',
        'support_email' => 'nullable|email|max:255',
        'website_url' => 'nullable|url|max:255',
        'is_default' => 'boolean',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $brand = Brand::findOrFail($id);
        $this->brandId = $brand->id;
        $this->name = $brand->name;
        $this->logo_url = $brand->logo_url;
        $this->primary_color = $brand->primary_color;
        $this->secondary_color = $brand->secondary_color;
        $this->footer_text = $brand->footer_text;
        $this->support_email = $brand->support_email;
        $this->website_url = $brand->website_url;
        $this->is_default = (bool) $brand->is_default;

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->is_default) {
            // Unset other defaults if this one is set to default
            Brand::where('is_default', true)->when($this->brandId, function ($query) {
                return $query->where('id', '!=', $this->brandId);
            })->update(['is_default' => false]);
        }

        if ($this->isEditing) {
            $brand = Brand::findOrFail($this->brandId);
            $brand->update([
                'name' => $this->name,
                'logo_url' => $this->logo_url,
                'primary_color' => $this->primary_color,
                'secondary_color' => $this->secondary_color,
                'footer_text' => $this->footer_text,
                'support_email' => $this->support_email,
                'website_url' => $this->website_url,
                'is_default' => $this->is_default,
            ]);
            session()->flash('success', 'Brand updated successfully.');
        } else {
            Brand::create([
                'name' => $this->name,
                'logo_url' => $this->logo_url,
                'primary_color' => $this->primary_color,
                'secondary_color' => $this->secondary_color,
                'footer_text' => $this->footer_text,
                'support_email' => $this->support_email,
                'website_url' => $this->website_url,
                'is_default' => $this->is_default,
            ]);
            session()->flash('success', 'Brand created successfully.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete($id)
    {
        $this->confirmingDeletion = true;
        $this->brandIdToDelete = $id;
    }

    public function delete()
    {
        $brand = Brand::findOrFail($this->brandIdToDelete);
        $brand->delete();
        $this->confirmingDeletion = false;
        session()->flash('success', 'Brand deleted successfully.');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->reset([
            'name',
            'logo_url',
            'primary_color',
            'secondary_color',
            'footer_text',
            'support_email',
            'website_url',
            'is_default',
            'brandId'
        ]);
        $this->primary_color = '#000000';
        $this->secondary_color = '#ffffff';
    }

    public function render()
    {
        $brands = Brand::where('name', 'like', '%' . $this->search . '%')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('livewire.brands.brand-manager', [
            'brands' => $brands
        ]);
    }
}
