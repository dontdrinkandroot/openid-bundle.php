<?php

namespace Dontdrinkandroot\OpenIdBundle\Tests\TestApp;

use Override;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    #[Override]
    public function getProjectDir(): string
    {
        return __DIR__;
    }

    #[Override]
    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/ddr_openid_bundle/cache/';
    }

    #[Override]
    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/ddr_openid_bundle/logs/';
    }
}
