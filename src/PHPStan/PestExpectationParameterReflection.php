<?php

declare(strict_types=1);

namespace BenTools\TestHttpClient\PHPStan;

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Reflection of a parameter of a custom Pest expectation.
 *
 * @internal
 */
final readonly class PestExpectationParameterReflection implements ParameterReflection
{
    private function __construct(
        private string $name,
        private Type $type,
        private bool $optional,
    ) {
    }

    public static function required(string $name, Type $type): self
    {
        return new self($name, $type, false);
    }

    /**
     * Optional parameters of these expectations always default to null.
     */
    public static function optionalNullable(string $name, Type $type): self
    {
        return new self($name, TypeCombinator::addNull($type), true);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function passedByReference(): PassedByReference
    {
        return PassedByReference::createNo();
    }

    public function isVariadic(): bool
    {
        return false;
    }

    public function getDefaultValue(): ?Type
    {
        return $this->optional ? new NullType() : null;
    }
}
