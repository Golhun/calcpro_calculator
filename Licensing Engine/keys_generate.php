<?php
declare(strict_types=1);

// keys_generate.php
// Run once on server, store private key securely, publish public key.

if (!extension_loaded('sodium')) {
    fwrite(STDERR, "Error: ext-sodium not installed.\n");
    exit(1);
}

$keypair = sodium_crypto_sign_keypair();
$publicKey = sodium_crypto_sign_publickey($keypair);
$secretKey = sodium_crypto_sign_secretkey($keypair);

$pubB64 = base64_encode($publicKey);
$secB64 = base64_encode($secretKey);

echo "PUBLIC_KEY_B64={$pubB64}\n";
echo "SECRET_KEY_B64={$secB64}\n";
