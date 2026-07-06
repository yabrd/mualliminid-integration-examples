<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::viaRequest('sso-token', function ($request) {
            $accessToken = $request->cookie('sso_access_token');

            if (!$accessToken) return null;

            $parts = explode('.', $accessToken);
            if (count($parts) !== 3) return null;

            $publicKeyPath = base_path(config('services.sso.public_key_path', 'keys/public.pem'));
            if (!file_exists($publicKeyPath)) return null;

            $publicKey = file_get_contents($publicKeyPath);
            $data = $parts[0] . '.' . $parts[1];
            $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[2]));

            if (openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
                cookie()->queue(cookie()->forget('sso_access_token'));
                cookie()->queue(cookie()->forget('sso_id_token'));
                return null;
            }

            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            $ssoId = $payload['userId'] ?? $payload['sub'] ?? null;
            $exp = $payload['exp'] ?? 0;

            if (!$ssoId) {
                cookie()->queue(cookie()->forget('sso_access_token'));
                cookie()->queue(cookie()->forget('sso_id_token'));
                return null;
            }

            if (time() >= $exp) {
                try {
                    $cookieHeader = $request->header('Cookie') ?? '';
                    $response = Http::withHeaders(['Cookie' => $cookieHeader])->post(config('services.sso.api_url') . '/auth/sso/token', [
                        'grant_type'    => 'refresh_token',
                        'client_id'     => config('services.sso.client_id'),
                        'client_secret' => config('services.sso.client_secret'),
                    ]);

                    if ($response->successful()) {
                        $responseData = $response->json()['data'] ?? [];
                        $newAccessToken = $responseData['access_token'] ?? null;
                        $newIdToken = $responseData['id_token'] ?? null;
                        $expiresIn = $responseData['expires_in'] ?? 900;

                        if (!$newAccessToken) return null;

                        $newParts = explode('.', $newAccessToken);
                        if (count($newParts) !== 3) return null;

                        $newRawData = $newParts[0] . '.' . $newParts[1];
                        $newSignature = base64_decode(str_replace(['-', '_'], ['+', '/'], $newParts[2]));

                        if (openssl_verify($newRawData, $newSignature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
                            cookie()->queue(cookie()->forget('sso_access_token'));
                            cookie()->queue(cookie()->forget('sso_id_token'));
                            return null;
                        }

                        cookie()->queue('sso_access_token', $newAccessToken, $expiresIn / 60, null, null, false, true);
                        cookie()->queue('sso_id_token', $newIdToken, $expiresIn / 60, null, null, false, true);

                        $newPayload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $newParts[1])), true);
                        $ssoId = $newPayload['userId'] ?? $newPayload['sub'] ?? null;
                    } else {
                        cookie()->queue(cookie()->forget('sso_access_token'));
                        cookie()->queue(cookie()->forget('sso_id_token'));
                        return null;
                    }
                } catch (\Exception $e) {
                    Log::error('SSO refresh token failed', ['error' => $e->getMessage()]);
                    return null;
                }
            }

            return User::where('sso_id', $ssoId)->first();
        });
    }
}
