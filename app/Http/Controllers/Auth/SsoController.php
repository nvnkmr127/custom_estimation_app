<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SsoController extends Controller
{
    /**
     * Redirect to the SSO provider.
     */
    public function login(Request $request)
    {
        if (!config('sso.enabled')) {
            return redirect()->route('login')->withErrors(['error' => 'SSO is currently disabled.']);
        }

        $redirectUrl = config('sso.auth_core_url') . '/login?redirect=' . urlencode(url()->previous());

        return redirect()->away($redirectUrl);
    }

    /**
     * Handle the SSO callback.
     */
    public function callback(\Illuminate\Http\Request $request)
    {
        try {
            // 1. Token Extraction
            $token = $request->get('token');

            if (!$token) {
                return redirect()->route('login')->withErrors(['error' => 'SSO Token is missing.']);
            }

            // 2. JWT Signature Verification (Micro-Task 3.2 & 7.1)
            try {
                $publicKey = config('sso.public_key');
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($publicKey, 'RS256'));
            } catch (\Firebase\JWT\ExpiredException $e) {
                \Illuminate\Support\Facades\Log::warning('SSO Token Expired', ['error' => $e->getMessage()]);
                abort(403, 'SSO token has expired.');
            } catch (\Firebase\JWT\SignatureInvalidException $e) {
                \Illuminate\Support\Facades\Log::warning('SSO Token Signature Invalid', ['error' => $e->getMessage()]);
                abort(403, 'Invalid SSO token signature.');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('SSO JWT Verification Failed', ['error' => $e->getMessage()]);
                abort(403, 'Invalid SSO token.');
            }

            // 3. Claim Validation (Audience, Issuer, JTI)
            // Audience check
            if (($decoded->aud ?? null) !== config('sso.jwt_audience')) {
                \Illuminate\Support\Facades\Log::warning('SSO Audience Mismatch', ['aud' => $decoded->aud ?? 'none']);
                abort(403, 'Invalid token audience.');
            }

            // Issuer check
            if (($decoded->iss ?? null) !== config('sso.auth_core_url')) {
                \Illuminate\Support\Facades\Log::warning('SSO Issuer Mismatch', ['iss' => $decoded->iss ?? 'none']);
                abort(403, 'Invalid token issuer.');
            }

            // JTI check (must exist)
            if (empty($decoded->jti)) {
                \Illuminate\Support\Facades\Log::warning('SSO Token Missing JTI');
                abort(403, 'Invalid token structure (missing JTI).');
            }

            // JTI Uniqueness Check (Replay Protection - Micro-Task 7.2)
            if (config('sso.jti_validation')) {
                $cacheKey = 'sso_jti:' . $decoded->jti;
                if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    \Illuminate\Support\Facades\Log::warning('SSO Token Replay Detected', ['jti' => $decoded->jti]);
                    abort(403, 'This token has already been used.');
                }
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, config('sso.jti_ttl', 3600));
            }

            // 4. User Identification/Creation (Micro-Task 4.1)
            $user = \App\Models\User::where('email', $decoded->email)->first();

            if ($user) {
                // Do not override existing users' basic info, but link SSO if not linked
                if (!$user->provider_name) {
                    $user->update([
                        'provider_name' => 'auth_core',
                        'provider_id' => $decoded->sub,
                        'source' => 'sso',
                    ]);
                }
            } else {
                // Auto-provisioning for new users
                $user = \App\Models\User::create([
                    'name' => $decoded->name ?? $decoded->email,
                    'email' => $decoded->email,
                    'provider_name' => 'auth_core',
                    'provider_id' => $decoded->sub,
                    'source' => 'sso',
                    'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
                    'role' => $this->mapRole($decoded->role ?? null),
                ]);
            }

            // 5. Secure Application Login (Micro-Task 5.1)
            \Illuminate\Support\Facades\Auth::login($user);

            // Regenerate session to prevent session fixation
            $request->session()->regenerate();

            \Illuminate\Support\Facades\Log::info('SSO Login Successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'jti' => $decoded->jti,
            ]);

            return redirect()->intended(route('dashboard'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SSO Callback Error: ' . $e->getMessage());

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                throw $e;
            }

            return redirect()->route('login')->withErrors([
                'error' => 'Authentication failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Map a JWT role to a local application role.
     */
    protected function mapRole(?string $jwtRole): string
    {
        $mapping = config('sso.role_mapping', []);

        if ($jwtRole && isset($mapping[$jwtRole])) {
            return $mapping[$jwtRole];
        }

        return config('sso.default_role', 'estimator');
    }
}
