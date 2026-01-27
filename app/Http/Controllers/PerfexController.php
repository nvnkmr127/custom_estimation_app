<?php

namespace App\Http\Controllers;

use App\Services\PerfexApiService;
use Illuminate\Http\Request;

class PerfexController extends Controller
{
    protected $api;

    public function __construct(PerfexApiService $api)
    {
        $this->api = $api;
    }

    public function index()
    {
        $response = $this->api->getLeads(50);

        // Perfex API returns a JSON array of objects directly for leads listing
        if (is_array($response) && isset($response['status']) && $response['status'] === false) {
            $leads = [];
            session()->flash('error', $response['message'] ?? $response['error'] ?? 'Perfex API Error');
        } elseif (isset($response['error'])) {
            $leads = [];
            session()->flash('error', $response['error']);
        } elseif (is_array($response) && !isset($response['data']) && !isset($response['status'])) {
            // Direct array of leads
            $leads = $response;
        } elseif (is_array($response) && isset($response['data'])) {
            // Wrapped in data (some Perfex versions)
            $leads = $response['data'];
        } else {
            $leads = [];
        }

        return view('perfex.index', compact('leads'));
    }

    public function import(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            return back()->with('error', 'Lead ID required.');
        }

        try {
            // Fetch Fresh Data from API (Safer than trusting POST data)
            $lead = $this->api->getLead($id);

            if (!$lead || isset($lead['status']) && $lead['status'] === false) {
                // Fallback: Use POST data if provided, or fail
                if ($request->has('name')) {
                    $lead = $request->all();
                } else {
                    return back()->with('error', 'Failed to fetch lead details from Perfex.');
                }
            }

            // Map Data
            $clientData = [
                'name' => $lead['name'] ?? $lead['lead_name'] ?? 'Unknown',
                'company' => $lead['company'] ?? null,
                'email' => $lead['email'] ?? null,
                'phone' => $lead['phonenumber'] ?? $lead['phone'] ?? null,
                'address' => $lead['address'] ?? null,
                'city' => $lead['city'] ?? null,
                'state' => $lead['state'] ?? null,
                'zip' => $lead['zip'] ?? null,
                'country' => $lead['country'] ?? null,
                'perfex_id' => $id,
                'property_name' => $lead['property_name'] ?? null,
                'property_address' => $lead['property_address'] ?? null,
                'property_notes' => $lead['property_notes'] ?? null,
            ];

            // Helper to extract from custom fields if missing
            if (isset($lead['customfields']) && is_array($lead['customfields'])) {
                foreach ($lead['customfields'] as $cf) {
                    $fieldName = strtolower($cf['name'] ?? '');
                    $val = $cf['value'] ?? '';

                    if (strpos($fieldName, 'property name') !== false && empty($clientData['property_name'])) {
                        $clientData['property_name'] = $val;
                    }
                    if (strpos($fieldName, 'property address') !== false && empty($clientData['property_address'])) {
                        $clientData['property_address'] = $val;
                    }
                }
            }

            // Save to Local DB
            $client = \App\Models\Client::updateOrCreate(
                ['perfex_id' => $id],
                $clientData
            );

            return back()->with('success', 'Lead imported successfully! Client ID: ' . $client->id);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Perfex Lead Import Failed', [
                'lead_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function sync(\App\Models\Estimate $estimate)
    {
        try {
            $result = $this->api->syncEstimate($estimate);

            if ($result['status']) {
                return back()->with('success', 'Synced to Perfex CRM! Proposal ID: ' . $result['id']);
            }

            return back()->with('error', 'Perfex Sync Failed: ' . $result['message']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Perfex Estimate Sync Failed', [
                'estimate_id' => $estimate->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function searchClients(Request $request)
    {
        $query = $request->get('q');

        if (empty($query)) {
            return response()->json([]);
        }

        // Cache for 10 minutes to avoid hitting rate limits or timeouts repeated
        $results = \Illuminate\Support\Facades\Cache::remember('perfex_search_' . md5($query), 600, function () use ($query) {
            return $this->api->searchLeads($query);
        });
        if (is_array($results) && !isset($results['status']) && !isset($results['error'])) {
            return response()->json($results);
        } elseif (is_array($results) && isset($results['data'])) {
            return response()->json($results['data']);
        }

        if (isset($results['error']) || (isset($results['status']) && $results['status'] === false)) {
            \Illuminate\Support\Facades\Log::warning('Perfex Lead Search API Returned Error', [
                'query' => $query,
                'response' => $results
            ]);
            // Return empty array to prevent client-side crash
            return response()->json([]);
        }

        return response()->json($results);
    }
}
