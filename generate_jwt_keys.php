<?php
// Générer les clés JWT RSA avec configuration minimale
$config = [
    'digest_alg' => 'sha256',
    'private_key_bits' => 2048,  // Réduit pour éviter les problèmes
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];

echo "Configuration: " . print_r($config, true) . "\n";

$res = openssl_pkey_new($config);

if ($res === false) {
    echo "Erreur: impossible de générer la clé privée\n";
    
    // Afficher tous les erreurs OpenSSL
    while ($err = openssl_error_string()) {
        echo "OpenSSL Error: $err\n";
    }
    exit(1);
}

$privKey = null;
if (!openssl_pkey_export($res, $privKey, null)) {
    echo "Erreur: impossible d'exporter la clé privée\n";
    while ($err = openssl_error_string()) {
        echo "OpenSSL Error: $err\n";
    }
    exit(1);
}

$details = openssl_pkey_get_details($res);
if ($details === false) {
    echo "Erreur: impossible de récupérer les détails de la clé\n";
    while ($err = openssl_error_string()) {
        echo "OpenSSL Error: $err\n";
    }
    exit(1);
}

$pubKey = $details['key'];

// Créer le répertoire s'il n'existe pas
if (!is_dir('config/jwt')) {
    mkdir('config/jwt', 0755, true);
}

file_put_contents('config/jwt/private.pem', $privKey);
file_put_contents('config/jwt/public.pem', $pubKey);

echo "✓ Clés JWT générées avec succès!\n";
echo "  - Private key: config/jwt/private.pem\n";
echo "  - Public key: config/jwt/public.pem\n";
