<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class SSOController extends Controller
{
    public function redirectToSSO()
    {
        $codeVerifier = Str::random(64);
        $codeChallenge = str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode(hash('sha256', $codeVerifier, true))
        );
        $state = Str::uuid()->toString();

        Session::put('sso_code_verifier', $codeVerifier);
        Session::put('sso_state', $state);

        $query = http_build_query([
            'client_id'             => config('services.sso.client_id'),
            'redirect_uri'          => route('sso.callback'),
            'response_type'         => 'code',
            'scope'                 => 'openid profile email',
            'state'                 => $state,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return redirect(config('services.sso.api_url') . '/auth/sso/authorize?' . $query);
    }

    public function handleCallback(Request $request)
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error');

        if ($error) {
            return redirect('/')->with('error', $request->query('error_description', 'Autentikasi gagal.'));
        }

        $savedState = Session::pull('sso_state');
        $codeVerifier = Session::pull('sso_code_verifier');

        if (!$state || $state !== $savedState) {
            return redirect('/')->with('error', 'State tidak cocok.');
        }

        $cookieHeader = $request->header('Cookie') ?? '';

        $response = Http::withHeaders([
            'Cookie' => $cookieHeader,
        ])->post(config('services.sso.api_url') . '/auth/sso/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.sso.client_id'),
            'client_secret' => config('services.sso.client_secret'),
            'code' => $code,
            'redirect_uri' => route('sso.callback'),
            'code_verifier' => $codeVerifier,
        ]);

        if (!$response->successful()) {
            return redirect('/')->with('error', 'Gagal menukar token.');
        }

        $tokens = $response->json()['data'] ?? [];
        $accessToken = $tokens['access_token'] ?? null;
        $idToken = $tokens['id_token'] ?? null;

        if (!$accessToken) {
            return redirect('/')->with('error', 'Access token tidak ditemukan dalam respons SSO.');
        }

        $userInfoResponse = Http::withToken($accessToken)
            ->get(config('services.sso.api_url') . '/auth/sso/userinfo');

        if (!$userInfoResponse->successful()) {
            return redirect('/')->with('error', 'Gagal mengambil data user.');
        }


        $ssoUser = $userInfoResponse->json()['data'];

        $parts = explode('.', $accessToken);
        $appRole = null;
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            $appRole = $payload['role'] ?? null;
        }

        $localUser = User::updateOrCreate(
            ['sso_id' => $ssoUser['userId']],
            [
                'name' => $ssoUser['name'] ?? explode('@', $ssoUser['email'])[0],
                'email' => $ssoUser['email'],
                'nbm' => $ssoUser['nbm'] ?? null,
                'whatsapp_number' => $ssoUser['whatsapp_number'] ?? null,
                'role' => $appRole,
            ]
        );

        $expiresIn = $tokens['expires_in'] ?? 900;

        cookie()->queue('sso_access_token', $accessToken, $expiresIn / 60, null, null, false, true);
        cookie()->queue('sso_id_token', $idToken, $expiresIn / 60, null, null, false, true);

        return redirect('/dashboard');
    }

    public function logout(Request $request)
    {
        $accessToken = $request->cookie('sso_access_token');
        $idToken = $request->cookie('sso_id_token');

        if ($accessToken && $idToken) {
            try {
                $cookieHeader = $request->header('Cookie') ?? '';

                Http::withHeaders([
                    'Cookie' => $cookieHeader,
                ])->withToken($accessToken)->post(config('services.sso.api_url') . '/auth/sso/end_session', [
                    'id_token_hint' => $idToken,
                    'client_id' => config('services.sso.client_id'),
                ]);
            } catch (\Exception $e) {
                Log::warning('SSO end_session request failed', ['error' => $e->getMessage()]);
            }
        }

        cookie()->queue(cookie()->forget('sso_access_token'));
        cookie()->queue(cookie()->forget('sso_id_token'));

        return redirect('/');
    }
}
