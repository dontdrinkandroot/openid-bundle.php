<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    // Only needed to satisfy LogoutAction's ManagerRegistry dependency;
    // league persistence is in-memory, so no entities are used.
    $container->extension('doctrine', [
        'dbal' => [
            'url' => '%env(resolve:DATABASE_URL)%',
        ],
        'orm' => [],
    ]);
};