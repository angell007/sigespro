<?php
return [
    'gmail_api' => [
        'credentials_path' => __DIR__ . '/../config/credentials/credentials.json', 
        'token_path' => __DIR__ . '/../config/credentials/token.json'
    ],
    'smtp_fallback' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'sistemas@prohsa.com',
        'password' => 'Sistemas2025', 
        'from_email' => 'sistemas@prohsa.com',
        'from_name' => 'ProH S.A.'
    ]
];