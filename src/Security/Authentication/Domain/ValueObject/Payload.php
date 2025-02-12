<?php

namespace App\Security\Authentication\Domain\ValueObject;

use Traversable;
use Webmozart\Assert\Assert;

readonly class Payload implements \ArrayAccess, \IteratorAggregate, \JsonSerializable, \Stringable
{
    public function __construct(
        private array $data = []
    ) {
        /**
         * This behavior is not RFC compliant, but it simplifies JWT validity checks.
         */
        Assert::inArray('iat', array_keys($this->data), 'JWT payload should contain an "iat" key');
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // JWT payload are immutable once created
        throw new \BadMethodCallException('JWT payload are read-only');
    }

    public function offsetUnset(mixed $offset): void
    {
        // JWT payload are immutable once created
        throw new \BadMethodCallException('JWT payload are read-only');
    }

    public function getIterator(): Traversable
    {
        yield from $this->data;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return json_encode($this);
    }
}
