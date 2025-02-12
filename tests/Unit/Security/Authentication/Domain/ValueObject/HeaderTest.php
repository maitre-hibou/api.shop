<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Authentication\Domain\ValueObject;

use App\Security\Authentication\Domain\ValueObject\Header;
use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{
    public function testHeaderCreation(): void
    {
        $header = new Header();

        $this->assertEquals(Header::TYP_JWT, $header->typ);
        $this->assertEquals(Header::ALG_RS256, $header->alg);
    }

    public function testHeaderCreationThrowsExceptionForInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value equal to "JWT". Got: "foo"');

        new Header('foo');
    }

    public function testHeaderCreationThrowsExceptionForUnsupportedAlg(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected one of: "HS256", "RS256". Got: "RS512"');

        new Header(Header::TYP_JWT, 'RS512');
    }

    public function testHeaderJsonSerialization(): void
    {
        $header = new Header();

        $this->assertEquals('{"typ":"JWT","alg":"RS256"}', json_encode($header));
    }

    public function testHeaderToStringConversion(): void
    {
        $header = new Header();

        $this->assertEquals('{"typ":"JWT","alg":"RS256"}', (string) $header);
    }
}
