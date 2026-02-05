<?php

declare(strict_types=1);

namespace BenTools\TestHttpClient\Bundle;

use BenTools\TestHttpClient\DependencyInjection\TestHttpClientExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @codeCoverageIgnore
 */
final class TestHttpClientBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new TestHttpClientExtension();
    }
}
