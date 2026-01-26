<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Zoho API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Zoho Books API integration using Self Client OAuth 2.0
    |
    */

    /**
     * Zoho domains by region
     */
    'domains' => [
        'com' => 'https://accounts.zoho.com',
        'eu' => 'https://accounts.zoho.eu',
        'in' => 'https://accounts.zoho.in',
        'au' => 'https://accounts.zoho.com.au',
        'jp' => 'https://accounts.zoho.jp',
    ],

    /**
     * API base URLs by region
     */
    'api_base' => [
        'com' => 'https://books.zoho.com/api/v3',
        'eu' => 'https://books.zoho.eu/api/v3',
        'in' => 'https://books.zoho.in/api/v3',
        'au' => 'https://books.zoho.com.au/api/v3',
        'jp' => 'https://books.zoho.jp/api/v3',
    ],

    /**
     * OAuth endpoints (relative to domain)
     */
    'oauth' => [
        'authorize_url' => '/oauth/v2/auth',
        'token_url' => '/oauth/v2/token',
        'revoke_url' => '/oauth/v2/token/revoke',
    ],

    /**
     * Available scopes for Zoho Books
     */
    'scopes' => [
        'books_full' => 'ZohoBooks.fullaccess.all',
        'books_read' => 'ZohoBooks.fullaccess.READ',
        'books_create' => 'ZohoBooks.fullaccess.CREATE',
        'books_update' => 'ZohoBooks.fullaccess.UPDATE',
        'books_delete' => 'ZohoBooks.fullaccess.DELETE',
    ],

    /**
     * Default configuration values
     */
    'defaults' => [
        'domain' => env('ZOHO_DEFAULT_DOMAIN', 'com'),
        'scope' => env('ZOHO_DEFAULT_SCOPE', 'ZohoBooks.fullaccess.all'),
        'timeout' => env('ZOHO_API_TIMEOUT', 30),
    ],

    /**
     * Rate limiting configuration
     */
    'rate_limiting' => [
        'requests_per_minute' => 100,
        'requests_per_day' => 2500,
    ],

    /**
     * Self Client configuration from environment
     * These should be set per connection, not globally
     */
    'self_client' => [
        'client_id' => env('ZOHO_CLIENT_ID'),
        'client_secret' => env('ZOHO_CLIENT_SECRET'),
        'redirect_uri' => env('ZOHO_REDIRECT_URI', env('APP_URL') . '/zoho/callback'),
    ],
];
