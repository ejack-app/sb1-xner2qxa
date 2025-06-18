<?php
// Placeholder for Integration Configurations
// In a real application, consider environment variables or a more secure way to store sensitive keys.

return [
    'salla' => [
        'enabled' => false,
        'api_key' => 'SALLA_API_KEY_PLACEHOLDER',
        'api_secret' => 'SALLA_API_SECRET_PLACEHOLDER',
        'webhook_url' => '/webhooks/salla.php', // Example
        'webhook_secret' => 'YOUR_SALLA_WEBHOOK_SECRET', // Added for webhook verification
        'base_url' => 'https://api.salla.dev/admin/v2/', // Example, check actual Salla API docs
    ],

    'zid' => [
        'enabled' => false,
        'api_key' => 'ZID_API_KEY_PLACEHOLDER',
        'auth_token' => 'ZID_AUTH_TOKEN_PLACEHOLDER', // Zid might use OAuth or other tokens
        'store_id' => 'ZID_STORE_ID_PLACEHOLDER',
        'webhook_url' => '/webhooks/zid.php', // Example
        'webhook_token' => 'YOUR_ZID_WEBHOOK_VERIFICATION_TOKEN', // Added for webhook verification
        'base_url' => 'https://api.zid.sa/v1/', // Example, check actual Zid API docs
    ],

    'citc' => [ // Communication and Information Technology Commission (Saudi Arabia)
        'enabled' => false,
        'api_key' => 'CITC_API_KEY_PLACEHOLDER',
        'username' => 'CITC_USERNAME_PLACEHOLDER',
        'password' => 'CITC_PASSWORD_PLACEHOLDER', // Or other auth mechanism
        'base_url' => 'https://api.citc.gov.sa/logistics/', // Hypothetical endpoint
        // Specific requirements for CITC integration would be needed (e.g., for shipment lifecycle reporting)
    ],

    'sms_service' => [
        'enabled' => false,
        'provider' => 'default_sms_provider', // e.g., 'twilio', 'unifonic', 'msggateway'
        'api_key' => 'SMS_PROVIDER_API_KEY',
        'api_secret_or_sender_id' => 'SMS_PROVIDER_API_SECRET_OR_SENDER_ID',
        'base_url' => 'PROVIDER_API_BASE_URL',
        // Provider specific settings
        'unifonic' => [
           'app_sid' => 'UNIFONIC_APP_SID',
           'sender_id' => 'LogisticsCo' // Example sender ID
        ],
        'msggateway_me' => [ // Example for another provider like www.msggateway.me
           'user_id' => 'MSG_GW_USER_ID',
           'password' => 'MSG_GW_PASSWORD',
           'sender_id' => 'FastShip'
        ]
    ],

    // Configuration for Salla App (if it's a public app on Salla's App Store)
    'salla_app_config' => [
        'enabled' => false,
        'client_id' => 'SALLA_APP_CLIENT_ID',
        'client_secret' => 'SALLA_APP_CLIENT_SECRET',
        // Might include scopes, redirect URIs etc.
    ],

    // Configuration for Zid App (if it's a public app on Zid's App Store)
    'zid_app_config' => [
        'enabled' => false,
        'client_id' => 'ZID_APP_CLIENT_ID',
        'client_secret' => 'ZID_APP_CLIENT_SECRET',
        // Might include scopes, redirect URIs etc.
    ]
];
?>
