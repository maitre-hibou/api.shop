<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use function App\urlsafe_base64_decode;
use function App\urlsafe_base64_encode;

class HelpersTest extends TestCase
{
    public function urlsafeB64EncodeProvider(): array
    {
        return [
            ['SGVsbG8gV29ybGQgIQ', 'Hello World !']
        ];
    }

    public function urlsafeB64DecodeProvider(): array
    {
        return [
            ['Hello World !', 'SGVsbG8gV29ybGQgIQ']
        ];
    }

    /**
     * @dataProvider urlSafeB64EncodeProvider
     */
    public function testUrlSafeB64Encode(string $expected, string $provided): void
    {
        $this->assertEquals($expected, urlsafe_base64_encode($provided));
    }

    /**
     * @dataProvider urlSafeB64DecodeProvider
     */
    public function testUrlSafeB64Decode(string $expected, string $provided): void
    {
        $this->assertEquals($expected, urlsafe_base64_decode($provided));
    }

}
