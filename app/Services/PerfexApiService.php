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
    public function searchLeads($query)
    {
        return $this->request('get', 'leads/search/' . urlencode($query));
    }

    /**
     * Create/Sync Proposal to Perfex
     */
    public function createProposal($data)
    {
        return $this->request('post', 'proposals', $data);
    }

    /**
     * Sync Estimate to Perfex
     */
    public function syncEstimate(\App\Models\Estimate $estimate)
    {
        $estimate->load('items');
        $client = \App\Models\Client::find($estimate->client_id);

        if (!$client || !$client->perfex_id) {
            return ['status' => false, 'message' => 'Client not linked to Perfex'];
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

        $response = $this->createProposal($proposalData);

        if (isset($response['status']) && $response['status'] == true && isset($response['id'])) {
            $estimate->perfex_proposal_id = $response['id'];
            $estimate->save();

            return ['status' => true, 'id' => $response['id']];
        }

        return ['status' => false, 'message' => $response['message'] ?? $response['error'] ?? 'Unknown API Error'];
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
                return $this->getMockResponse($endpoint, $data);
            }

            Log::error('Perfex API Configuration Invalid');
            return ['status' => false, 'message' => 'API Configuration Missing. Please configure in Settings.'];
        }

        // Ensure URL structure contains /api/ if relying on standard REST module patterns
        // Some users put full URL with /api in settings, some don't. We try to be smart.
        if (strpos($this->baseUrl, '/api') === false && strpos($endpoint, 'api/') !== 0) {
            $endpoint = 'api/' . ltrim($endpoint, '/');
        }

        // Remove trailing slash from base URL if present
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        try {
            $response = Http::timeout(5)
                        ->withHeaders([
                            'authtoken' => $this->token,
                            // Fallback if user is using a module that expects bearer
                            // 'Authorization' => 'Bearer ' . $this->token 
                        ])->$method($url, $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Perfex API Error: ' . $response->status() . ' - ' . $body = $response->body() . ' | Request: ' . json_encode($data) . ' | URL: ' . $url);

            return ['status' => false, 'error' => 'API Error: ' . $response->status() . ' ' . substr($body, 0, 100)];

        } catch (\Exception $e) {
            Log::error('Perfex API Exception: ' . $e->getMessage());

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate Mock Data for Testing
     */
    protected function getMockResponse($endpoint, $data)
    {
        // Mock Search
        if (strpos($endpoint, 'leads/search') !== false) {
            // Extract query from URL if possible, or just return generic
            return [
                [
                    'id' => 991,
                    'name' => 'John Doe (Demo)',
                    'company' => 'Acme Corp',
                    'email' => 'john@example.com',
                    'phonenumber' => '555-0101',
                    'city' => 'Metropolis',
                    'state' => 'NY',
                    'country' => 'USA',
                    'address' => '123 Fake St',
                    'zip' => '10001'
                ],
                [
                    'id' => 992,
                    'name' => 'Jane Smith (Demo)',
                    'company' => 'Stark Ind',
                    'email' => 'jane@example.com',
                    'phonenumber' => '555-0102',
                    'city' => 'Malibu',
                    'state' => 'CA',
                    'country' => 'USA',
                    'address' => '10880 Malibu Point',
                    'zip' => '90265'
                ]
            ];
        }

        // Mock Get Single Lead
        if (preg_match('/leads\/\d+/', $endpoint)) {
            return [
                'id' => 991,
                'name' => 'John Doe (Demo)',
                'company' => 'Acme Corp',
                'email' => 'john@example.com',
                'phonenumber' => '555-0101',
                'city' => 'Metropolis',
                'state' => 'NY',
                'country' => 'USA',
                'address' => '123 Fake St',
                'zip' => '10001',
                'status' => 1
            ];
        }

        // Mock List
        if ($endpoint === 'leads') {
            return [
                [
                    'id' => 991,
                    'name' => 'John Doe (Demo)',
                    'company' => 'Acme Corp'
                ]
            ];
        }

        // Mock Proposal Create
        if ($endpoint === 'proposals') {
            return ['status' => true, 'id' => 888, 'message' => 'Mock Proposal Created'];
        }

        return ['status' => false, 'message' => 'Mock Endpoint Not Found'];
    }
}
