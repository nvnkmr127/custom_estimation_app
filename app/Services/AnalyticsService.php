<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\EstimateAnalytic;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;

class AnalyticsService
{
    public function logAccess(Estimate $estimate, string $action)
    {
        // Don't log if logged in as admin/staff?
        // Requirement: "Track online PDF views... Estimate user location..."
        // Often we want to exclude internal views, but for now let's log everything OR check current user role.
        // Let's assume we log all accesses for now to be safe, or maybe filter out 'staff' if needed.
        // For MVP, log everything.

        $agent = new Agent;
        $userAgent = Request::header('User-Agent');
        $agent->setUserAgent($userAgent);
        
        // Security/Compliance: Filter out bots to prevent data inflation
        if ($agent->isRobot()) {
            return;
        }

        $ip = Request::ip();
        
        // Privacy: Anonymize IP and UserAgent for GDPR compliance
        // We truncate the last octet and store a truncated/hashed version to prevent full PII storage
        $anonymizedIp = preg_replace('/(\d+)\.(\d+)\.(\d+)\.(\d+)/', '$1.$2.$3.0', $ip);
        // If we can't change schema, we store the hash in the user_agent column
        $uaHash = hash('sha256', $userAgent);

        // Check uniqueness: Same Anon-IP & UA Hash within last 24 hours
        $existing = EstimateAnalytic::where('estimate_id', $estimate->id)
            ->where('ip_address', $anonymizedIp)
            ->where('user_agent', $uaHash)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        $isUnique = !$existing;
        $location = $this->resolveLocation($ip);

        EstimateAnalytic::create([
            'estimate_id' => $estimate->id,
            'action' => $action,
            'ip_address' => $anonymizedIp,
            'user_agent' => $uaHash,
            'device' => $agent->device(),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'location_json' => $location,
            'is_unique' => $isUnique,
        ]);
    }

    protected function resolveLocation($ip)
    {
        // For local development, mock
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return ['city' => 'Localhost', 'country' => 'Devland'];
        }

        // Use a free API like ip-api.com (Rate limited: 45 requests per minute)
        // Warning: HTTP call adds latency. Should be queued in production.
        // For this task, we'll do sync but handle errors gracefully.

        return \Illuminate\Support\Facades\Cache::remember("ip_loc_{$ip}", 86400, function () use ($ip) {
            try {
                $json = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,city");
                if ($json) {
                    $data = json_decode($json, true);
                    if (isset($data['status']) && $data['status'] === 'success') {
                        return [
                            'city' => $data['city'] ?? 'Unknown',
                            'country' => $data['country'] ?? 'Unknown',
                        ];
                    }
                }
            } catch (\Exception $e) {
                // log error
            }
            return null;
        });
    }
}
