<?php
namespace Tests\Support;

class SochitelFixtures
{
    public static function keys(): array
    {
        static $keys;
        if (!$keys) {
            $key = openssl_pkey_new(['private_key_bits' => 4096, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $private);
            $keys = ['private' => $private, 'public' => openssl_pkey_get_details($key)['key']];
        }
        return $keys;
    }
    public static function operators(): array
    {
        $data = function ($id, $name, $local, $cost, $type = '4') {
            return ['id' => (string) $id, 'name' => $name, 'type' => $type, 'category' => $type . '.0', 'amount' => ['type' => 'fixed', 'min' => ['operator' => $local, 'user' => $cost], 'max' => ['operator' => $local, 'user' => $cost]], 'extraParameters' => false];
        };
        $range = $data(21, 'Ghana airtime', '1.00', '0.08', '1');
        $range['amount']['type'] = 'range';
        $range['amount']['max'] = ['operator' => '100.00', 'user' => '8.00'];
        $extra = $data(14, 'Extra fields required', '50.00', '4.00');
        $extra['extraParameters'] = ['accountType' => ['mandatory' => true]];
        return [
            ['id' => '7', 'name' => 'Ghana Network', 'country' => 'GH', 'currency' => 'GHS', 'productTypes' => ['1', '4'], 'products' => [$data(11, '5GB 30 days', '50.00', '4.00'), $data(12, '10GB 7 days', '50.00', '5.00'), $data(13, 'PIN voucher', '50.00', '4.00', '2'), $extra, $range]],
            ['id' => '8', 'name' => 'Nigeria Network', 'country' => 'NG', 'currency' => 'NGN', 'productTypes' => ['4'], 'products' => [$data(31, '1GB Daily', '500.00', '1.00')]],
        ];
    }
}
