<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 60, 80, 100]) ? $perPage : 10;

        $stats = [
            'total' => User::count(),
            'synced' => User::whereNotNull('sso_id')->count(),
            'no_role' => User::whereNull('role')->count(),
        ];

        $users = User::select(['id', 'name', 'email', 'role', 'nbm', 'whatsapp_number'])
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'stats' => $stats,
            'users' => $users,
        ]);
    }

    public function sync()
    {
        try {
            $page = 1;
            $totalPages = 1;
            $count = 0;
            $limit = 100;
            $syncedSsoIds = [];

            do {
                $response = Http::withHeaders([
                    'X-Client-Secret' => config('services.sso.client_secret'),
                ])->get(config('services.sso.api_url') . '/client-apps/by-client-id/' . config('services.sso.client_id') . '/users', [
                    'page' => $page,
                    'limit' => $limit,
                ]);

                if (!$response->successful()) {
                    return response()->json(['message' => 'Gagal sinkronisasi: Respon API SSO tidak sukses.'], 400);
                }

                $json = $response->json();
                $ssoUsers = $json['data'] ?? [];
                $meta = $json['meta'] ?? [];
                $totalPages = $meta['totalPages'] ?? 1;

                foreach ($ssoUsers as $ssoUser) {
                    if (empty($ssoUser['userId'])) {
                        continue;
                    }

                    User::updateOrCreate(
                        ['sso_id' => $ssoUser['userId']],
                        [
                            'name' => $ssoUser['name'] ?? explode('@', $ssoUser['email'])[0],
                            'email' => $ssoUser['email'],
                            'nbm' => $ssoUser['nbm'] ?? null,
                            'whatsapp_number' => $ssoUser['whatsapp_number'] ?? null,
                            'role' => $ssoUser['role'] ?? null,
                        ]
                    );
                    $syncedSsoIds[] = $ssoUser['userId'];
                    $count++;
                }

                $page++;
            } while ($page <= $totalPages);

            if (count($syncedSsoIds) > 0) {
                User::whereNotNull('sso_id')
                    ->whereNotIn('sso_id', $syncedSsoIds)
                    ->where('role', '!=', 'ADMIN')
                    ->delete();
            }

            return response()->json([
                'status' => 'success',
                'message' => "Sinkronisasi berhasil! {$count} pengguna telah diselaraskan."
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal melakukan sinkronisasi: ' . $e->getMessage()], 500);
        }
    }
}
