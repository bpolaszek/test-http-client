<?php

declare(strict_types=1);

namespace BenTools\TestHttpClient\Tests\Fixtures;

use BenTools\TestHttpClient\TestKernelTrait;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends BaseKernel
{
    use MicroKernelTrait;
    use TestKernelTrait;

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
        ];
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/bentools-test-http-client/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/bentools-test-http-client/logs';
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', [
            'secret' => 'test-secret',
            'test' => true,
            'router' => [
                'utf8' => true,
            ],
            'profiler' => [
                'enabled' => true,
                'collect' => false,
            ],
        ]);

        // Register the test controller
        $container->register(TestController::class)
            ->setAutoconfigured(true)
            ->setAutowired(true)
            ->setPublic(true);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('test_get', '/test')
            ->controller([TestController::class, 'get'])
            ->methods(['GET']);

        $routes->add('test_post_json', '/json')
            ->controller([TestController::class, 'postJson'])
            ->methods(['POST']);

        $routes->add('test_protected', '/protected')
            ->controller([TestController::class, 'protected'])
            ->methods(['GET']);

        $routes->add('test_headers', '/headers')
            ->controller([TestController::class, 'headers'])
            ->methods(['GET']);

        $routes->add('test_error', '/error/{code}')
            ->controller([TestController::class, 'error'])
            ->methods(['GET']);
    }
}
