<?php

namespace Dontdrinkandroot\OpenIdBundle\Config;

class RouteName
{
    public const string BUNDLE_PREFIX = 'ddr.openid';
    public const string LOGOUT = self::BUNDLE_PREFIX . '.logout';
    public const string USERINFO = self::BUNDLE_PREFIX . '.userinfo';
    public const string CONFIGURATION = self::BUNDLE_PREFIX . '.configuration';
    public const string JWKS = self::BUNDLE_PREFIX . '.jwks';
}
