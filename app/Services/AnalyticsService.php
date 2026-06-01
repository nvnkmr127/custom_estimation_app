<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\EstimateAnalytic;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;

class AnalyticsService
{
    public function logAccess(Estimate $estimate, string $action): void
    {
        $agent = new Agent;
        $userAgent = Request::header('User-Agent', '');
        $agent->setUserAgent($userAgent);

        if ($agent->isRobot()) {
            return;
        }

        $ip = Request::ip();

        $anonymizedIp = $this->anonymizeIp($ip);
        $uaHash = hash('sha256', $userAgent);

        $isUnique = !EstimateAnalytic::where('estimate_id', $estimate->id)
            ->where('ip_address', $anonymizedIp)
            ->where('user_agent', $uaHash)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        $location = $this->resolveLocation($ip);

        EstimateAnalytic::create([
            'estimate_id'   => $estimate->id,
            'action'        => $action,
            'ip_address'    => $anonymizedIp,
            'user_agent'    => $uaHash,
            'device'        => $agent->device() ?: $this->detectDeviceType($agent),
            'browser'       => $agent->browser() ?: 'Unknown',
            'platform'      => $agent->platform() ?: 'Unknown',
            'location_json' => $location,
            'is_unique'     => $isUnique,
        ]);
    }

    protected function anonymizeIp(string $ip): string
    {
        // IPv4: zero last octet
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/(\d+\.\d+\.\d+)\.\d+/', '$1.0', $ip);
        }

        // IPv6: zero last 80 bits (keep first 48 bits)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', inet_ntop(inet_pton($ip)));
            return implode(':', array_slice($parts, 0, 3)) . '::';
        }

        return '0.0.0.0';
    }

    protected function detectDeviceType(Agent $agent): string
    {
        if ($agent->isMobile()) return 'Mobile';
        if ($agent->isTablet()) return 'Tablet';
        return 'Desktop';
    }

    protected function resolveLocation(string $ip): ?array
    {
        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return ['city' => 'Localhost', 'country' => 'Devland'];
        }

        return \Illuminate\Support\Facades\Cache::remember("ip_loc_{$ip}", 86400, function () use ($ip) {
            try {
                $ctx = stream_context_create(['http' => ['timeout' => 3]]);
                $json = file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,city", false, $ctx);
                if ($json) {
                    $data = json_decode($json, true);
                    if (isset($data['status']) && $data['status'] === 'success') {
                        return [
                            'city'    => $data['city'] ?? 'Unknown',
                            'country' => $data['country'] ?? 'Unknown',
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning("IP geolocation failed for {$ip}: " . $e->getMessage());
            }
            return null;
        });
    }
}
