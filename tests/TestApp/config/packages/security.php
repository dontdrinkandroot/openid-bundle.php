<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('security', [
        'providers' => [
            'test_users' => [
                'memory' => [
                    'users' => [
                        'user-1' => ['password' => 'password-1'],
                    ],
                ],
            ],
        ],
        'firewalls' => [
            'main' => [
                'pattern' => '^/',
                'stateless' => true,
                'provider' => 'test_users',
            ],
        ],
    ]);
};