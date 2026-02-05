<?php

declare(strict_types=1);

namespace BenTools\TestHttpClient\DependencyInjection;

use BenTools\TestHttpClient\TestHttpClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @codeCoverageIgnore
 */
final class TestHttpClientExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Register KernelBrowser as a service
        $browserDefinition = new Definition(KernelBrowser::class);
        $browserDefinition->setPublic(true);
        $browserDefinition->setArguments([
            new Reference('kernel'),
        ]);
        $container->setDefinition(KernelBrowser::class, $browserDefinition);

        // Register TestHttpClient as a service
        $definition = new Definition(TestHttpClient::class);
        $definition->setPublic(true);
        $definition->setArguments([
            new Reference(KernelBrowser::class),
        ]);
        $container->setDefinition(TestHttpClient::class, $definition);
    }
}
