<?php

namespace Dontdrinkandroot\OpenIdBundle\Config;

use Dontdrinkandroot\OpenIdBundle\Config\DependencyInjection\TagName;
use Dontdrinkandroot\OpenIdBundle\Controller\JwksAction;
use Dontdrinkandroot\OpenIdBundle\Controller\LogoutAction;
use Dontdrinkandroot\OpenIdBundle\Controller\OpenidConfigurationAction;
use Dontdrinkandroot\OpenIdBundle\Controller\UserInfoAction;
use Dontdrinkandroot\OpenIdBundle\Event\Listener\NonceListener;
use Dontdrinkandroot\OpenIdBundle\Model\IdTokenResponse;
use Dontdrinkandroot\OpenIdBundle\Service\CryptService;
use Dontdrinkandroot\OpenIdBundle\Service\Nonce\CachedNonceService;
use Dontdrinkandroot\OpenIdBundle\Service\Nonce\NonceServiceInterface;
use Dontdrinkandroot\OpenIdBundle\Service\ScopeProvider\OpenIdScopeProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use Symfony\Component\HttpFoundation\RequestStack;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->set(UserInfoAction::class)
        ->autowire()
        ->autoconfigure()
        ->arg('$scopeProviders', tagged_iterator(TagName::SCOPE_PROVIDER))
        ->tag(TagName::CONTROLLER_SERVICE_ARGUMENTS);

    $services->set(OpenIdScopeProvider::class)
        ->tag(TagName::SCOPE_PROVIDER);

    $services->set(JwksAction::class)
        ->autowire()
        ->autoconfigure()
        ->arg('$publicKeyPath', env('resolve:OAUTH_PUBLIC_KEY'))
        ->tag(TagName::CONTROLLER_SERVICE_ARGUMENTS);

    $services->set(OpenidConfigurationAction::class)
        ->autowire()
        ->autoconfigure()
        ->tag(TagName::CONTROLLER_SERVICE_ARGUMENTS);

    $services->set(LogoutAction::class)
        ->autowire()
        ->autoconfigure()
        ->tag(TagName::CONTROLLER_SERVICE_ARGUMENTS);

    $services->set(IdTokenResponse::class)
        ->arg('$requestStack', service(RequestStack::class))
        ->arg('$cryptService', service(CryptService::class))
        ->arg('$nonceService', service(NonceServiceInterface::class));

    $services->set(CryptService::class)
        ->arg('$encryptionKey', param('league.oauth2_server.encryption_key'));

    $services->set(CachedNonceService::class)
        ->arg('$cacheAdapter', service('cache.app'));

    $services->alias(NonceServiceInterface::class, CachedNonceService::class);

    $services->set(NonceListener::class)
        ->arg('$nonceService', service(NonceServiceInterface::class))
        ->arg('$cryptService', service(CryptService::class))
        ->tag('kernel.event_listener', ['event' => 'kernel.response', 'method' => 'onKernelResponse']);
};
