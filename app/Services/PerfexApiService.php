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
        return $this->request('get', 'leads/search/'.urlencode($query));
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

        if (! $client || ! $client->perfex_id) {
            return ['status' => false, 'message' => 'Client not linked to Perfex'];
        }

        $proposalData = [
            'subject' => $estimate->title ?? 'Estimate #'.$estimate->estimate_number,
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
        if (empty($this->baseUrl) || empty($this->token)) {
            Log::error('Perfex API Configuration Invalid');

            return ['status' => false, 'message' => 'API Configuration Missing'];
        }

        // Remove trailing slash from base URL if present
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($endpoint, '/');

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'authtoken' => $this->token, // Perfex Standard Header often 'authtoken' or 'Authorization'
                    // Some Perfex implementations use 'Authorization: Bearer' if OAuth, but many use 'authtoken' for simple API keys.
                    // If user uses a standard module, it looks for 'authtoken'.
                    // We will try standard 'authtoken'.
                ])->$method($url, $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Perfex API Error: '.$response->status().' - '.$response->body().' | Request: '.json_encode($data).' | URL: '.$url);

            return ['status' => false, 'error' => 'API Error: '.$response->status()];

        } catch (\Exception $e) {
            Log::error('Perfex API Exception: '.$e->getMessage());

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }
}
