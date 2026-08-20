<?php

declare(strict_types=1);

namespace BenTools\TestHttpClient\PHPStan;

use Pest\Expectation;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;

use function in_array;

/**
 * Makes the custom Pest expectations shipped by this package known to PHPStan.
 *
 * They are registered at runtime through `expect()->extend()` (see
 * src/pest-expectations.php), which no static analyzer can discover on its own.
 */
final class PestExpectationsClassReflectionExtension implements MethodsClassReflectionExtension
{
    private const EXPECTATIONS = [
        'toHaveStatusCode',
        'toHaveHeader',
        'toBeSuccessful',
        'toBeClientError',
        'toBeServerError',
        'toHaveJsonStructure',
    ];

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return Expectation::class === $classReflection->getName()
            && in_array($methodName, self::EXPECTATIONS, true);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return new PestExpectationMethodReflection(
            $classReflection,
            $methodName,
            $this->getParameters($methodName),
        );
    }

    /**
     * @return list<ParameterReflection>
     */
    private function getParameters(string $methodName): array
    {
        return match ($methodName) {
            'toHaveStatusCode' => [
                PestExpectationParameterReflection::required('statusCode', new IntegerType()),
            ],
            'toHaveHeader' => [
                PestExpectationParameterReflection::required('header', new StringType()),
                PestExpectationParameterReflection::optionalNullable('value', new StringType()),
            ],
            'toHaveJsonStructure' => [
                PestExpectationParameterReflection::optionalNullable('keys', new ArrayType(new MixedType(), new StringType())),
            ],
            default => [],
        };
    }
}
