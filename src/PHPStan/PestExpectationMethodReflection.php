<?php

declare(strict_types=1);

namespace BenTools\TestHttpClient\PHPStan;

use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;

/**
 * Reflection of a custom Pest expectation registered by this package.
 *
 * The return type is a StaticType, so that chained calls keep the resolved
 * generic (e.g. `Pest\Expectation<Response>`) instead of degrading to a bare
 * `Pest\Expectation`.
 *
 * @internal
 */
final readonly class PestExpectationMethodReflection implements MethodReflection
{
    /**
     * @param list<ParameterReflection> $parameters
     */
    public function __construct(
        private ClassReflection $declaringClass,
        private string $name,
        private array $parameters,
    ) {
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    public function isStatic(): bool
    {
        return false;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getDocComment(): ?string
    {
        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrototype(): ClassMemberReflection
    {
        return $this;
    }

    public function getVariants(): array
    {
        return [
            new FunctionVariant(
                TemplateTypeMap::createEmpty(),
                null,
                $this->parameters,
                false,
                new StaticType($this->declaringClass),
            ),
        ];
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getThrowType(): ?Type
    {
        return null;
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
}
