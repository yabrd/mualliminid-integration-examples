<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    const PROTECTED_ROLE = 'ADMIN';

    public $perPage = 10;
    public $perPageOptions = [10, 20, 60, 80, 100];

    protected $queryString = ['perPage' => ['except' => 10, 'as' => 'per_page']];

    public function updatingPerPage()
    {
        $this->resetPage();
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
                    $this->dispatch('show-toast', message: 'Gagal sinkronisasi: Respon API SSO tidak sukses.', type: 'error');
                    return;
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
                    ->where('role', '!=', self::PROTECTED_ROLE)
                    ->delete();
            }

            $this->dispatch('show-toast', message: "Sinkronisasi berhasil! {$count} pengguna telah diselaraskan.", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Gagal melakukan sinkronisasi: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $stats = [
            'total' => User::count(),
            'synced' => User::whereNotNull('sso_id')->count(),
            'no_role' => User::whereNull('role')->count(),
        ];

        $localUsers = User::select(['id', 'name', 'email', 'role', 'nbm', 'whatsapp_number'])
            ->paginate($this->perPage);

        $headers = ['Nama', 'Email', 'Role', 'NBM', 'WhatsApp'];
        $columns = [
            ['key' => 'name', 'type' => 'title'],
            ['key' => 'email', 'type' => 'text'],
            ['key' => 'role', 'type' => 'badge'],
            ['key' => 'nbm', 'type' => 'code'],
            ['key' => 'whatsapp_number', 'type' => 'code'],
        ];

        return view('livewire.user-management', compact('localUsers', 'stats', 'headers', 'columns'));
    }
}
