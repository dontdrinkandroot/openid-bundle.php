<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('league_oauth2_server', [
        'authorization_server' => [
            // Keys are required for container compilation; only touched when the
            // AuthorizationServer is actually used (not on the discovery endpoint).
            'private_key' => '%env(resolve:OAUTH_PRIVATE_KEY)%',
            'encryption_key' => 'test-encryption-key-not-secret',
            'enable_client_credentials_grant' => true,
            'enable_password_grant' => true,
            'enable_auth_code_grant' => true,
            'enable_implicit_grant' => false,
            'enable_refresh_token_grant' => true,
        ],
        'resource_server' => [
            'public_key' => '%env(resolve:OAUTH_PUBLIC_KEY)%',
        ],
        'scopes' => [
            'available' => ['openid', 'profile', 'api'],
            'default' => ['openid'],
        ],
        'persistence' => [
            'in_memory' => null,
        ],
    ]);
};