<?php

namespace Dontdrinkandroot\OpenIdBundle\DependencyInjection;

use Dontdrinkandroot\ApiPlatformBundle\Model\DependencyInjection\ParamName;
use Override;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class DdrOpenIdExtension extends Extension implements PrependExtensionInterface
{
    #[Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');
    }

    #[Override]
    public function prepend(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');
        assert(is_array($bundles));
        if (array_key_exists('NelmioCorsBundle', $bundles)) {
            $container->prependExtensionConfig('nelmio_cors', [
                'paths' => [
                    '^/(.well-known/(openid-configuration|jwks.json)|oauth2/token)' => [
                        'allow_credentials' => true,
                        'allow_origin' => ['*'],
                    ]
                ]
            ]);
        }
    }
}
