<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'secret' => 'test-secret',
        'router' => [
            'utf8' => true,
        ],
        'test' => true,
    ]);

    // Alias is derived from DdrOpenIdBundle: ddr_open_id (not ddr_openid).
    $container->extension('ddr_open_id', [
        'whitelisted_clients' => [],
        'resolve_user_provider' => 'security.user.provider.concrete.test_users',
    ]);
};