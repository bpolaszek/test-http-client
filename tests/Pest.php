<?php

declare(strict_types=1);

use BenTools\TestHttpClient\Tests\Fixtures\TestKernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Create a new KernelBrowser instance for testing.
 */
function createClient(): KernelBrowser
{
    $kernel = new TestKernel('test', true);
    return new KernelBrowser($kernel);
}
