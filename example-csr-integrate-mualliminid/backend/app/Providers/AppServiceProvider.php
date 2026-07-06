<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Auth::viaRequest('sso-token', function ($request) {
            $accessToken = $request->bearerToken();

            if (!$accessToken) return null;

            $parts = explode('.', $accessToken);
            if (count($parts) !== 3) return null;

            $base64UrlDecode = function ($input) {
                $remainder = strlen($input) % 4;
                if ($remainder) {
                    $padlen = 4 - $remainder;
                    $input .= str_repeat('=', $padlen);
                }
                return base64_decode(str_replace(['-', '_'], ['+', '/'], $input));
            };

            $publicKeyPath = base_path(config('services.sso.public_key_path', 'keys/public.pem'));
            if (!file_exists($publicKeyPath)) {
                return null;
            }

            $publicKey = file_get_contents($publicKeyPath);
            $data = $parts[0] . '.' . $parts[1];
            $signature = $base64UrlDecode($parts[2]);

            if (openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
                return null;
            }

            $payloadJson = $base64UrlDecode($parts[1]);
            $payload = json_decode($payloadJson, true);
            $ssoId = $payload['userId'] ?? $payload['sub'] ?? null;
            $exp = $payload['exp'] ?? 0;

            if (!$ssoId || time() >= $exp) {
                return null;
            }

            $user = User::where('sso_id', $ssoId)->first();
            if (!$user) {
                try {
                    $response = Http::withToken($accessToken)->get(config('services.sso.api_url') . '/auth/sso/userinfo');
                    if ($response->successful()) {
                        $ssoUser = $response->json()['data'] ?? [];
                        $email = $ssoUser['email'] ?? null;
                        if ($email) {
                            $user = User::where('email', $email)->first();
                            $userData = [
                                'sso_id' => $ssoId,
                                'name' => $ssoUser['name'] ?? explode('@', $email)[0],
                                'nbm' => $ssoUser['nbm'] ?? null,
                                'whatsapp_number' => $ssoUser['whatsapp_number'] ?? null,
                                'role' => $payload['role'] ?? null,
                            ];
                            if ($user) {
                                $user->update($userData);
                            } else {
                                $userData['email'] = $email;
                                $user = User::create($userData);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    return null;
                }
            }

            return $user;
        });
    }
}
