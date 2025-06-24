<?php

namespace Dontdrinkandroot\OpenIdBundle\Config\DependencyInjection;

class ParamName
{
    private const string BUNDLE_PREFIX = 'ddr.openid';
    public const string WHITELISTED_CLIENTS = self::BUNDLE_PREFIX . '.whitelisted_clients';
}
