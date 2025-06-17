<?php

namespace Dontdrinkandroot\OpenIdBundle\Config;

use Dontdrinkandroot\OpenIdBundle\Controller\JwksAction;
use Dontdrinkandroot\OpenIdBundle\Controller\LogoutAction;
use Dontdrinkandroot\OpenIdBundle\Controller\OpenidConfigurationAction;
use Dontdrinkandroot\OpenIdBundle\Controller\UserInfoAction;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->import('@LeagueOAuth2ServerBundle/config/routes.php', 'php')
        ->prefix('oauth2');

    $routes->add(RouteName::LOGOUT, 'oauth2/logout')
        ->controller(LogoutAction::class)
        ->methods(['GET', 'POST']);

    $routes->add(RouteName::USERINFO, 'oauth2/userinfo')
        ->controller(UserInfoAction::class)
        ->methods(['GET']);

    $routes->add(RouteName::CONFIGURATION, '.well-known/openid-configuration')
        ->controller(OpenidConfigurationAction::class)
        ->methods(['GET']);

    $routes->add(RouteName::JWKS, '.well-known/jwks.json')
        ->controller(JwksAction::class)
        ->methods(['GET']);

};
