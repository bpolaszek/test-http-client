<?php

declare(strict_types=1);

namespace BenTools\TestHttpClient\Tests\Unit;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function dirname;

final class PestExpectationsPHPStanExtensionTest extends TypeInferenceTestCase
{
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(dirname(__DIR__).'/Fixtures/phpstan/pest-expectations.php');
    }

    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__, 2).'/extension.neon'];
    }
}
