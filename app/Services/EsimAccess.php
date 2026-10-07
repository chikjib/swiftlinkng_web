<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class EsimAccess {
    public function request(string $path, array $body = []): array {
        abort_unless(config('esim.access_code'),503,'Travel eSIM is being configured.');
        // One shared provider limit across customer calls, admin and reconciliation.
        $slot = Cache::lock('esim-access-request-slot', 10);
        $slot->block(5, function () {
            $last = (float) Cache::get('esim-access-last-request', 0);
            $wait = 0.14 - (microtime(true) - $last);
            if ($wait > 0) usleep((int) ($wait * 1000000));
            Cache::put('esim-access-last-request', microtime(true), 60);
        });
        $response = Http::acceptJson()->withHeaders(['RT-AccessCode'=>config('esim.access_code')])
                ->connectTimeout(10)->timeout(40)->post(config('esim.base_url').'/'.$path, $body ?: (object) []);
            if (!$response->successful() || !is_array($response->json())) throw new \RuntimeException('eSIM provider connection unavailable.');
        return $response->json();
    }
    public function data(string $path, array $body = []): array {
        $result = $this->request($path, $body);
        if (($result['success'] ?? null) !== true || !is_array($result['obj'] ?? null)) throw new \RuntimeException('eSIM details are temporarily unavailable.');
        return $result['obj'];
    }
    public function packages(string $location = '', ?object $profile = null): array {
        $body = $profile ? ['type'=>'TOPUP','iccid'=>$profile->iccid] : ['type'=>'BASE','locationCode'=>$location];
        return $this->data('package/list', $body)['packageList'] ?? [];
    }
}
