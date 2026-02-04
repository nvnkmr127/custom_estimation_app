<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct()
    {
        // Manual authorization used instead of authorizeResource to support custom lookup logic
    }

    /**
     * Display a listing of clients.
     */
    public function index()
    {
        $this->authorize('viewAny', Client::class);

        $clients = Client::withCount('estimates')
            ->orderBy('name')
            ->paginate(20);

        return view('clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new client.
     */
    public function create()
    {
        $this->authorize('create', Client::class);

        return view('clients.create');
    }

    /**
     * Store a newly created client.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Client::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'property_name' => 'nullable|string|max:255',
            'property_address' => 'nullable|string|max:255',
            'property_notes' => 'nullable|string',
        ]);

        Client::create($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Client created successfully.');
    }

    /**
     * Display the specified client.
     */
    public function show($id)
    {
        // Try to find by ID first, then by Perfex ID
        $client = Client::where('id', $id)->orWhere('perfex_id', $id)->first();

        if (!$client) {
            abort(404);
        }

        $this->authorize('view', $client);

        if (request()->wantsJson()) {
            return response()->json($client);
        }

        $client->load([
            'estimates' => function ($query) {
                $query->latest()->limit(10);
            },
        ]);

        return view('clients.show', compact('client'));
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(Client $client)
    {
        $this->authorize('update', $client);

        return view('clients.edit', compact('client'));
    }

    /**
     * Update the specified client.
     */
    public function update(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'property_name' => 'nullable|string|max:255',
            'property_address' => 'nullable|string|max:255',
            'property_notes' => 'nullable|string',
        ]);

        $client->update($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Client updated successfully.');
    }

    /**
     * Remove the specified client.
     */
    public function destroy(Client $client)
    {
        $this->authorize('delete', $client);

        // Check if client has estimates
        if ($client->estimates()->count() > 0) {
            return redirect()->route('clients.index')
                ->with('error', 'Cannot delete client with existing estimates.');
        }

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted successfully.');
    }
}
