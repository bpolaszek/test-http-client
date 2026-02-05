<?php

declare(strict_types=1);

use BenTools\TestHttpClient\Tests\Fixtures\TestKernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses()->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../src/Pest/Expectations.php';

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Create a new KernelBrowser instance for testing.
 */
function createClient(): KernelBrowser
{
    $kernel = new TestKernel('test', true);
    return new KernelBrowser($kernel);
}
