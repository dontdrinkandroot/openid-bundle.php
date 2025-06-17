<?php

namespace Dontdrinkandroot\OpenIdBundle\Config\DependencyInjection;

class TagName
{
    public const string CONTROLLER_SERVICE_ARGUMENTS = 'controller.service_arguments';

    private const string BUNDLE_PREFIX = 'ddr.openid';
    public const string SCOPE_PROVIDER = self::BUNDLE_PREFIX . '.scope_provider';
}
