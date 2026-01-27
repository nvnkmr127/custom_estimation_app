<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    public function getLeads($limit = 20)
    {
        return $this->request('get', 'leads', ['limit' => $limit]);
    }

    /**
     * Get a single lead
     */
    public function getLead($id)
    {
        return $this->request('get', "leads/$id");
    }

    /**
     * Get Staff/Members (for owner mapping)
     */
    public function getStaff()
    {
        return $this->request('get', 'staff');
    }

    /**
     * Search Leads
     */
    /**
     * Search Leads
     */
    public function searchLeads($query)
    {
        // Use rawurlencode to ensure spaces become %20, not +, which CodeIgniter might dislike in URI segments
        return $this->request('get', 'leads/search/' . rawurlencode($query));
    }

    /**
     * Create/Sync Proposal to Perfex
     */
    public function createProposal($data)
    {
        return $this->request('post', 'proposals', $data);
    }

    /**
     * Update existing Proposal in Perfex
     */
    public function updateProposal($id, $data)
    {
        return $this->request('put', "proposals/$id", $data);
    }

    /**
     * Create Lead in Perfex
     */
    public function createLead($data)
    {
        return $this->request('post', 'leads', $data);
    }

    /**
     * Sync Estimate to Perfex
     */
    public function syncEstimate(\App\Models\Estimate $estimate)
    {
        $estimate->load('items');
        $client = \App\Models\Client::find($estimate->client_id);

        if (!$client) {
            return ['status' => false, 'message' => 'Client not found'];
        }

        // Auto-link or create client in Perfex if missing
        if (!$client->perfex_id) {
            $this->findOrCreatePerfexClient($client);
        }

        if (!$client->perfex_id) {
            return ['status' => false, 'message' => 'Client could not be synced to Perfex CRM'];
        }

        $proposalData = [
            'subject' => $estimate->title ?? 'Estimate #' . $estimate->estimate_number,
            'rel_type' => 'lead', // or customer
            'rel_id' => $client->perfex_id,
            'proposal_to' => $client->name,
            'date' => \Carbon\Carbon::parse($estimate->estimate_date)->format('Y-m-d'),
            'open_till' => $estimate->expiry_date ? \Carbon\Carbon::parse($estimate->expiry_date)->format('Y-m-d') : null,
            'currency' => 1,
            'subtotal' => $estimate->subtotal,
            'total' => $estimate->grand_total,
            'content' => 'Generated via Custom Estimation App. See attached PDF.',
            'status' => 6, // Draft
        ];

        // START FIX: Update if exists, Create if new
        if ($estimate->perfex_proposal_id) {
            $response = $this->updateProposal($estimate->perfex_proposal_id, $proposalData);

            if (isset($response['status']) && $response['status'] == true) {
                return ['status' => true, 'id' => $estimate->perfex_proposal_id, 'action' => 'updated'];
            }

            // If update failed (e.g. deleted in CRM), try create? 
            // For now, logging error.
            Log::warning("Perfex Update Failed for Proposal #{$estimate->perfex_proposal_id}, trying creation.");
        }
        // END FIX

        $response = $this->createProposal($proposalData);

        if (isset($response['status']) && $response['status'] == true && isset($response['id'])) {
            $estimate->perfex_proposal_id = $response['id'];
            $estimate->save();

            return ['status' => true, 'id' => $response['id'], 'action' => 'created'];
        }

        return ['status' => false, 'message' => $response['message'] ?? $response['error'] ?? 'Unknown API Error'];
    }

    /**
     * Helper to find or create client in Perfex based on email
     */
    protected function findOrCreatePerfexClient(\App\Models\Client $client)
    {
        if (empty($client->email))
            return false;

        // 1. Search by email
        $searchResults = $this->searchLeads($client->email);

        if (!empty($searchResults) && is_array($searchResults) && isset($searchResults[0]['id'])) {
            $client->perfex_id = $searchResults[0]['id'];
            $client->save();
            return true;
        }

        // 2. If not found, create new Lead
        $leadData = [
            'name' => $client->name,
            'email' => $client->email,
            'address' => $client->address,
            'status' => 2, // 'Contacted' or Configurable default
            'source' => 5, // 'Web' or Configurable default
        ];

        $createResponse = $this->createLead($leadData);
        if (isset($createResponse['status']) && $createResponse['status'] == true && isset($createResponse['id'])) {
            $client->perfex_id = $createResponse['id'];
            $client->save();
            return true;
        }

        return false;
    }

    /**
     * Create Task in Perfex
     */
    public function createTask($data)
    {
        // $data usually contains: name, description, rel_type (lead/proposal/customer), rel_id, etc.
        return $this->request('post', 'tasks', $data);
    }

    /**
     * Core Request Handler
     */
    protected function request($method, $endpoint, $data = [])
    {
        // 1. Check Config
        if (empty($this->baseUrl) || empty($this->token)) {

            // MOCK MODE FOR LOCAL DEV
            if (app()->environment('local')) {
                return $this->getMockResponse($endpoint, $data, $method);
            }

            Log::error('Perfex API Configuration Invalid');
            return ['status' => false, 'message' => 'API Configuration Missing. Please configure in Settings.'];
        }

        // 1b. Explicit Mock Mode Check
        if (config('services.perfex.mock_mode') || env('PERFEX_MOCK_MODE')) {
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
            // Increase PHP execution time for this request to ensure we don't hit the 30s limit before Guzzle
            set_time_limit(90);

            // Dynamic configuration based on environment
            $isLocal = app()->environment('local');
            $timeout = $isLocal ? 5 : 60; // Fail fast in local (5s) to trigger mock, robust in prod (60s)
            $connectTimeout = $isLocal ? 2 : 20;
            $retries = $isLocal ? 0 : 3; // Don't retry in local, fail fast to mock

            $request = Http::timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->withUserAgent('Laravel/PerfexClient')
                ->withHeaders([
                    'authtoken' => $this->token,
                    'Connection' => 'keep-alive',
                ]);

            if ($retries > 0) {
                $request->retry($retries, 100);
            }

            $response = $request->$method($url, $data);

            if ($response->successful()) {
                return $response->json();
            }

            // Detect if HTML error (e.g. 400 Bad Request from Firewall/CI)
            if ($response->status() === 400 && str_contains($response->body(), 'HTML')) {
                Log::warning("Perfex API 400 HTML Error (Disallowed Chars?): " . $url);
            }

            // Graceful Fallback for Local Development on 5xx Errors
            if ($response->serverError() && app()->environment('local')) {
                Log::warning("Perfex API Server Error (5xx). Falling back to Mock Data. Status: " . $response->status());
                return $this->getMockResponse($endpoint, $data, $method);
            }

            Log::error('Perfex API Error: ' . $response->status() . ' - ' . $body = $response->body() . ' | Request: ' . json_encode($data) . ' | URL: ' . $url);

            return ['status' => false, 'error' => 'API Error: ' . $response->status() . ' ' . substr($body, 0, 100)];

        } catch (\Exception $e) {
            // Graceful Fallback for Local Development
            // Now catches ConnectionException specifically or any Guzzle error
            if (app()->environment('local')) {
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

        return ['status' => false, 'message' => 'Mock Endpoint Not Found: ' . $endpoint];
    }
}
