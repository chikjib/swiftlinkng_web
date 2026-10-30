<?php

require __DIR__.'/../vendor/autoload.php';

// Generate disposable keys in memory. The suite must not depend on the local
// .env file or read production Passport keys from storage.
$key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
openssl_pkey_export($key, $privateKey);
foreach (['PASSPORT_PRIVATE_KEY' => $privateKey, 'PASSPORT_PUBLIC_KEY' => openssl_pkey_get_details($key)['key']] as $name => $value) {
    $_ENV[$name] = $_SERVER[$name] = $value;
    putenv($name.'='.$value);
}
