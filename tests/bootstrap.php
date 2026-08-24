<?php

declare(strict_types=1);

use BenTools\TestHttpClient\Tests\Fixtures\TestKernel;

use function BenTools\Pest\Symfony\app;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Boot the kernel here, before any test runs and before PHPUnit installs its
// per-test error handler. bentools/pest-symfony-kernel boots it through a
// throwaway anonymous KernelTestCase; if a deprecation fires during that boot
// (container compilation, class autoloading, ...) while a real test's error
// handler is active, PHPUnit misattributes it to that anonymous instance
// (PHPUnit\Util\Test::currentTestCase() picks the nearest TestCase on the call
// stack), and crashes with AssertionError: assert(method_exists(...)) since
// its "method name" is just a uniqid(). Booting outside the per-test window
// sidesteps that entirely, regardless of which dependency happens to
// deprecate something.
$_SERVER['KERNEL_CLASS'] = $_ENV['KERNEL_CLASS'] = TestKernel::class;

app();
