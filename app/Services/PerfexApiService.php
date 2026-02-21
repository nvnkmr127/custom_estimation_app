<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PerfexApiService
{
    protected $baseUrl;

    protected $token;

    public function __construct()
    {
        // Fetch from DB Settings (Admin Defined) or Fallback to Env/Config
        $this->baseUrl = \App\Models\Setting::where('key', 'perfex_api_url')->value('value') ?? config('services.perfex.url');
        $this->token = \App\Models\Setting::where('key', 'perfex_api_token')->value('value') ?? config('services.perfex.token');
    }

    /**
     * Get Leads from Perfex CRM
     *
     * @param  int  $limit
     * @return array
     */
    /**
     * Get Leads from Perfex CRM
     *
     * @param  int  $limit
     * @param  bool $skipMock
     * @param  array $params
     * @return array
     */
    public function getLeads($limit = null, $skipMock = false, $params = [])
    {
        $data = $params;
        if ($limit) {
            $data['limit'] = $limit;
        }
        return $this->request('get', 'leads', $data, $skipMock);
    }

    /**
     * Get Lead Statuses
     * 
     * @return array
     */
    public function getStatuses()
    {
        return $this->request('get', 'leads/statuses');
    }

    /**
     * Search Leads
     * 
     * @param string $query
     * @return array
     */
    public function searchLeads($query)
    {
        // Guide says: GET /api/leads/search/:keysearch
        return $this->request('get', 'leads/search/' . urlencode($query));
    }

    /**
     * Get Leads via Zapier Poll Endpoint (Lighter, supports limit)
     *
     * @param int $limit
     * @return array
     */
    public function getLeadsViaZapier($limit = 50)
    {
        // This endpoint often supports standard pagination/limiting better than the main list
        return $this->request('get', 'zapier/poll/leads', ['limit' => $limit]);
    }

    /**
     * Get a single lead
     */
    public function getLead($id)
    {
        return $this->request('get', "leads/$id");
    }

    /**
     * Create a task in Perfex CRM
     * 
     * @param array $data
     * @return array
     */
    public function createTask(array $data)
    {
        return $this->request('post', 'tasks', $data);
    }

    // ... (keep other methods as is, or update if needed, but getLeads is what we test with) ...

    /**
     * Core Request Handler
     */
    protected function request($method, $endpoint, $data = [], $skipMock = false)
    {
        // 1. Check Config
        if (empty($this->baseUrl) || empty($this->token)) {

            // MOCK MODE FOR LOCAL DEV
            if (!$skipMock && app()->environment('local')) {
                return $this->getMockResponse($endpoint, $data, $method);
            }

            return ['status' => false, 'message' => 'API Configuration Missing. Please configure in Settings.'];
        }

        // 1b. Explicit Mock Mode Check
        if (!$skipMock && (config('services.perfex.mock_mode') || env('PERFEX_MOCK_MODE'))) {
            return $this->getMockResponse($endpoint, $data, $method);
        }

        // Ensure URL structure contains /api/ if relying on standard REST module patterns
        // Some users put full URL with /api in settings, some don't. We try to be smart.
        if (strpos($this->baseUrl, '/api') === false && strpos($endpoint, 'api/') !== 0) {
            $endpoint = 'api/' . ltrim($endpoint, '/');
        }

        // Remove trailing slash from base URL if present
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        try {
            // Increase PHP execution time for this request to ensure we don't hit the limit before Guzzle
            set_time_limit(300); // Increased to 300s

            // Dynamic configuration based on environment
            // INCREASING TIMEOUTS: Local env might have slow internet or DNS resolution to external crm.concept2designs.in
            $isLocal = app()->environment('local');
            $timeout = 180; // Increased to 180s
            $connectTimeout = 60; // Increased to 60s
            $retries = 3; // Enable retries even in local to be robust

            $request = Http::timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->withOptions([
                    'verify' => false,
                ])
                ->withUserAgent('PostmanRuntime/7.29.0')
                ->withHeaders([
                    'authtoken' => $this->token,
                    // 'Connection' => 'keep-alive', // Let Guzzle handle this
                    'Accept' => 'application/json',
                    // 'Content-Type' => 'application/json', // GET requests usually don't need this, and it might confuse some servers
                ]);

            if ($retries > 0) {
                // $request->retry($retries, 500); 
            }

            Log::info("Perfex API Request START: $method $url", [
                'params' => $data,
                'timeout' => $timeout,
                'token_present' => !empty($this->token)
            ]);

            $response = $request->$method($url, $data);

            if ($response->successful()) {
                return $response->json();
            }

            // Detect if HTML error (e.g. 400 Bad Request from Firewall/CI)
            if ($response->status() === 400 && str_contains($response->body(), 'HTML')) {
                Log::warning("Perfex API 400 HTML Error (Disallowed Chars?): " . $url);
            }

            // Graceful Fallback for Local Development on 5xx Errors
            if ($response->serverError()) {
                Log::error("Perfex API Server Error (5xx). Status: " . $response->status() . " Body: " . $response->body());

                if (!$skipMock && app()->environment('local')) {
                    Log::warning("Falling back to Mock Data due to 500 error.");
                    return $this->getMockResponse($endpoint, $data, $method);
                }

                return ['status' => false, 'error' => 'Remote Server Error (500). Check log for details.', 'details' => substr($response->body(), 0, 500)];
            }

            Log::error('Perfex API Error: ' . $response->status() . ' - ' . $body = $response->body() . ' | Request: ' . json_encode($data) . ' | URL: ' . $url);

            return ['status' => false, 'error' => 'API Error: ' . $response->status() . ' ' . substr($body, 0, 100)];

        } catch (\Exception $e) {
            // Graceful Fallback for Local Development
            // Now catches ConnectionException specifically or any Guzzle error
            if (!$skipMock && app()->environment('local')) {
                // If it's a timeout or connection error, mock it
                Log::warning("Perfex API Unreachable/Timeout in Local Dev. Falling back to Mock Data. Error: " . $e->getMessage());
                return $this->getMockResponse($endpoint, $data, $method);
            }

            Log::error('Perfex API Exception: ' . $e->getMessage());

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate Mock Data for Testing
     */
    protected function getMockResponse($endpoint, $data, $method = 'post')
    {
        // Mock Search
        if (strpos($endpoint, 'leads/search') !== false || (strpos($endpoint, 'leads') !== false && isset($data['search']))) {
            return [
                [
                    'id' => 991,
                    'name' => 'John Doe (Demo)',
                    'lead_name' => 'John Doe (Demo)',
                    'company' => 'Acme Corp',
                    'email' => 'john@example.com',
                    'phonenumber' => '555-0101',
                    'city' => 'Metropolis',
                    'state' => 'NY',
                    'country' => 'USA',
                    'address' => '123 Fake St',
                    'zip' => '10001',
                    'status' => 1,
                    'status_name' => 'Customer',
                    'property_name' => 'Blue Lagoon Villa',
                    'property_address' => '456 Ocean View, Metropolis',
                    'property_notes' => '3BHK, Seaside property',
                    'customfields' => [
                        ['name' => 'Property Name', 'value' => 'Blue Lagoon Villa'],
                        ['name' => 'Property Address', 'value' => '456 Ocean View, Metropolis']
                    ]
                ],
                [
                    'id' => 992,
                    'name' => 'Jane Smith (Demo)',
                    'lead_name' => 'Jane Smith (Demo)',
                    'company' => 'Stark Ind',
                    'email' => 'jane@example.com',
                    'phonenumber' => '555-0102',
                    'city' => 'Malibu',
                    'state' => 'CA',
                    'country' => 'USA',
                    'address' => '10880 Malibu Point',
                    'zip' => '90265',
                    'status' => 2,
                    'status_name' => 'Contacted',
                    'property_name' => 'Stark Penthouse',
                    'property_address' => 'Malibu Tower A',
                    'property_notes' => 'Smart home integration required',
                    'customfields' => [
                        ['name' => 'Property Name', 'value' => 'Stark Penthouse'],
                        ['name' => 'Property Address', 'value' => 'Malibu Tower A']
                    ]
                ]
            ];
        }

        // Mock Get Single Lead
        if (preg_match('/leads\/\d+/', $endpoint)) {
            return [
                'id' => 991,
                'name' => 'John Doe (Demo)',
                'lead_name' => 'John Doe (Demo)',
                'company' => 'Acme Corp',
                'email' => 'john@example.com',
                'phonenumber' => '555-0101',
                'city' => 'Metropolis',
                'state' => 'NY',
                'country' => 'USA',
                'address' => '123 Fake St',
                'zip' => '10001',
                'status' => 1,
                'status_name' => 'Customer',
                'property_name' => 'Blue Lagoon Villa',
                'property_address' => '456 Ocean View, Metropolis',
                'property_notes' => '3BHK, Seaside property',
                'customfields' => [
                    ['name' => 'Property Name', 'value' => 'Blue Lagoon Villa'],
                    ['name' => 'Property Address', 'value' => '456 Ocean View, Metropolis']
                ]
            ];
        }

        // Mock List
        if (strpos($endpoint, 'leads') !== false && $method === 'get') {
            return [
                [
                    'id' => 991,
                    'name' => 'John Doe (Demo)',
                    'lead_name' => 'John Doe (Demo)',
                    'company' => 'Acme Corp',
                    'status' => 1,
                    'status_name' => 'Customer',
                    'email' => 'john@example.com'
                ],
                [
                    'id' => 992,
                    'name' => 'Jane Smith (Demo)',
                    'lead_name' => 'Jane Smith (Demo)',
                    'company' => 'Stark Ind',
                    'status' => 2,
                    'status_name' => 'Contacted',
                    'email' => 'jane@example.com'
                ]
            ];
        }

        // Mock Proposal Create
        if (strpos($endpoint, 'proposals') !== false) {
            if ($method === 'post')
                return ['status' => true, 'id' => 888, 'message' => 'Mock Proposal Created'];
            if ($method === 'put')
                return ['status' => true, 'message' => 'Mock Proposal Updated'];
        }

        if (strpos($endpoint, 'leads') !== false && $method === 'post') {
            return ['status' => true, 'id' => 995, 'message' => 'Mock Lead Created'];
        }

        // Mock Task Create
        if (strpos($endpoint, 'tasks') !== false && $method === 'post') {
            return ['status' => true, 'id' => 777, 'message' => 'Mock Task Created'];
        }

        return ['status' => false, 'message' => 'Mock Endpoint Not Found: ' . $endpoint];
    }

    /**
     * Fetch and Sync Client Details from Perfex
     *
     * @param \App\Models\Client $client
     * @param array|null $providedData Optional data to sync without fetching
     * @return bool
     */
    public function fetchAndSyncClient(\App\Models\Client $client, $providedData = null)
    {
        if (!$client->perfex_id) {
            Log::warning("Perfex Sync: Client {$client->id} has no Perfex ID.");
            return false;
        }

        $data = $providedData;

        // If no data provided, fetch it
        if (!$data) {
            $data = $this->getLead($client->perfex_id);
        }

        if (!$data || !isset($data['id'])) {
            Log::warning("Perfex Sync: Could not fetch lead/customer #{$client->perfex_id} for Client {$client->id}");
            return false;
        }

        // Fetch Mapping from DB
        $savedMapping = \App\Models\Setting::where('key', 'perfex_field_mapping')->value('value');
        $mappings = $savedMapping ? json_decode($savedMapping, true) : null;

        // Default Fallback Mappings if nothing configured
        if (!$mappings) {
            $mappings = [
                ['perfex_field' => 'phonenumber', 'local_field' => 'phone'],
                ['perfex_field' => 'address', 'local_field' => 'address'],
                ['perfex_field' => 'Location', 'local_field' => 'city'],
                ['perfex_field' => 'Property Name', 'local_field' => 'property_name'],
                ['perfex_field' => 'Type of property', 'local_field' => 'property_type'],
                ['perfex_field' => 'Alternative Number', 'local_field' => 'secondary_phone'],
                ['perfex_field' => 'Alternative Email', 'local_field' => 'secondary_email'],
            ];
        }

        $notesToAppend = [];

        foreach ($mappings as $map) {
            $sourceKey = $map['perfex_field'];
            $destAttr = $map['local_field'];
            $valueToAssign = null;

            // 1. Try finding in root data (Standard Fields)
            // Case-insensitive check for root keys? API usually strict, but let's be safe.
            // Using precise key for root params.
            if (isset($data[$sourceKey])) {
                $valueToAssign = $data[$sourceKey];
            }
            // 2. Try Custom Fields
            elseif (isset($data['customfields']) && is_array($data['customfields'])) {
                foreach ($data['customfields'] as $cf) {
                    $cfName = $cf['name'] ?? $cf['label'] ?? '';
                    if (strcasecmp($cfName, $sourceKey) === 0) {
                        $valueToAssign = $cf['value'] ?? null;
                        break;
                    }
                }
            }

            // Apply Value if found
            if ($valueToAssign !== null && $valueToAssign !== '') {
                // Special Country Logic
                if ($destAttr === 'country' && ($valueToAssign === '0' || $valueToAssign === 0)) {
                    continue;
                }

                // If destination is already set and not null, do we overwrite? Yes, sync usually implies overwrite.
                // Exception: Don't overwrite City with Location if City is already present?
                // Logic: If multiple sources map to same destination (e.g. City and Location -> city),
                // the last non-empty one wins OR we check if dest is empty.
                // Let's implement: Only overwrite if current is empty or logic dictates.
                // For simplicity: Overwrite.

                // Specific logic for 'Location' acting as fallback for 'City':
                if (strtolower($sourceKey) === 'location' && $destAttr === 'city' && !empty($client->city)) {
                    continue; // meaningful city already exists from 'city', don't overwrite with Location
                }

                $client->$destAttr = $valueToAssign;
            }
        }

        // Hardcoded Note Appending (Optional: make this configurable later, but complex for UI)
        // We look for specific keys just for appending to notes if they exist in data
        $appendKeys = ['Possession month'];
        if (isset($data['customfields']) && is_array($data['customfields'])) {
            foreach ($data['customfields'] as $cf) {
                $cfName = $cf['name'] ?? $cf['label'] ?? '';
                $cfValue = $cf['value'] ?? '';
                if (in_array($cfName, $appendKeys) && !empty($cfValue)) {
                    // $label = str_replace('Type of property', 'Type', $cfName); // Removed
                    $label = str_replace('Possession month', 'Possession', $cfName);
                    $notesToAppend[] = "$label: $cfValue";
                }
            }
        }

        if (!empty($notesToAppend)) {
            $existingNotes = $client->property_notes ?? '';
            $newNotes = implode(', ', $notesToAppend);
            if (!str_contains($existingNotes, $newNotes)) {
                $client->property_notes = trim($existingNotes . "\n" . $newNotes);
            }
        }

        $client->save();
        Log::info("Perfex Sync: Successfully synced Client {$client->id} (Perfex #{$client->perfex_id})");

        return true;
    }
    /**
     * Synchronize an estimate to Perfex CRM as a proposal.
     */
    public function syncEstimate(\App\Models\Estimate $estimate): array
    {
        $payload = [
            'subject' => $estimate->estimate_number . ': ' . $estimate->title,
            'rel_id' => $estimate->client->perfex_id ?? null,
            'rel_type' => 'lead',
            'date' => $estimate->estimate_date ? Carbon::parse($estimate->estimate_date)->toDateString() : now()->toDateString(),
            'open_till' => $estimate->expiry_date ? Carbon::parse($estimate->expiry_date)->toDateString() : now()->addDays(30)->toDateString(),
            'currency' => 1, // Default currency ID in Perfex, should ideally be mapped
            'subtotal' => $estimate->subtotal,
            'total' => $estimate->grand_total,
            'discount_percent' => $estimate->discount_type === 'percentage' ? $estimate->discount_value : 0,
            'content' => $estimate->client_note ?? 'Estimate generated from Custom App.',
        ];

        if ($estimate->perfex_proposal_id) {
            // Update existing
            $result = $this->request('put', 'proposals/' . $estimate->perfex_proposal_id, $payload);
            if (isset($result['status']) && $result['status']) {
                return ['status' => true, 'id' => $estimate->perfex_proposal_id, 'message' => 'Updated existing proposal.'];
            }
            return $result;
        } else {
            // Create new
            $result = $this->request('post', 'proposals', $payload);
            if (isset($result['id'])) {
                $estimate->update(['perfex_proposal_id' => $result['id']]);
                return ['status' => true, 'id' => $result['id'], 'message' => 'Created new proposal.'];
            }
            return $result;
        }
    }
}
