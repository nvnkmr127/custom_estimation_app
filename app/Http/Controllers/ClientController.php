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
    public function index(Request $request)
    {
        $this->authorize('viewAny', Client::class);

        $query = Client::withCount('estimates');

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('city')) {
            $query->where('city', $request->input('city'));
        }

        if ($request->filled('has_estimates')) {
            if ($request->input('has_estimates') === 'yes') {
                $query->has('estimates');
            } else {
                $query->doesntHave('estimates');
            }
        }

        $clients = $query->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // Calculate Stats
        $stats = [
            'total' => Client::count(),
            'with_estimates' => Client::has('estimates')->count(),
            'recent' => Client::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        // Get unique cities for filter dropdown
        $cities = Client::whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city');

        if ($request->wantsJson()) {
            $mappedData = collect($clients->items())->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                ];
            });

            return response()->json([
                'current_page' => $clients->currentPage(),
                'data' => $mappedData,
                'total' => $clients->total(),
                'per_page' => $clients->perPage(),
                'last_page' => $clients->lastPage(),
            ]);
        }

        if ($request->ajax()) {
            return view('clients._table', compact('clients'));
        }

        return view('clients.index', compact('clients', 'stats', 'cities'));
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
        $client = Client::findOrFail($id);


        $this->authorize('view', $client);

        if (request()->wantsJson()) {
            return response()->json($client);
        }

        $client->load([
            'estimates' => function ($query) {
                $query->latest()->limit(20);
            },
        ]);

        // Calculate Client Stats
        $stats = [
            'total_estimates' => $client->estimates()->count(),
            'accepted_estimates' => $client->estimates()->where('estimate_status', \App\Models\Estimate::EST_STATUS_ACCEPTED)->count(),
            'total_value' => $client->estimates()->where('estimate_status', \App\Models\Estimate::EST_STATUS_ACCEPTED)->sum('grand_total'),
            'last_engagement' => $client->estimates()->latest('updated_at')->first()?->updated_at ?? $client->created_at,
        ];

        return view('clients.show', compact('client', 'stats'));
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
