<?php

namespace Dontdrinkandroot\OpenIdBundle;

use Override;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DdrOpenIdBundle extends Bundle
{
    #[Override]
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
