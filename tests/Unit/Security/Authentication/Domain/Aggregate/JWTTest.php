<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Authentication\Domain\Aggregate;

use App\Security\Authentication\Domain\Aggregate\JWT;
use App\Security\Authentication\Domain\Exception\InvalidJWTException;
use App\Security\Authentication\Domain\ValueObject\Header;
use App\Security\Authentication\Domain\ValueObject\Payload;
use PHPUnit\Framework\TestCase;
use function App\urlsafe_base64_encode;

class JWTTest extends TestCase
{
    public function testJWTHmacSignature(): void
    {
        $jwt = new JWT(
            new Header(alg: Header::ALG_HS256),
            new Payload([
                'iat' => '1665409277',
                'hello' => 'world',
            ])
        );

        $jwt->sign('5ecr3tKeY');

        $this->assertEquals('YjY0ZmE1MmU0YjU5OTE1YTk1MTkxNzg2MjZiMDczMzU5OGUzNjI2ZjVmZjBhYjg3ZDJlODVjYTU2ZWZhMTEwOA', $jwt->signature());
    }

    public function testJWTOpenSSLSignature(): void
    {
        $jwt = new JWT(
            new Header(),
            new Payload([
                'iat' => '1665409277',
                'hello' => 'world',
            ])
        );

        $jwt->sign(openssl_pkey_get_private(
            file_get_contents(sprintf('%s/.stubs/certs/private.pem', dirname(__DIR__, 5))),
            'secret')
        );

        $this->assertEquals('fv0_h1g4O_OzHOCGbT9biPSwSNiaIKaUqjRSnzZwq4ojXIkG_9cyxW4ac4oLpi3rutT4RszSGw-jKotOT_tah8UESkGZBlVCtKz0KkuVbDacEO4UMOwizSvX8suY31Nw_4TvKciQOmi_XEBAvEDmfo0IaYRTPro0IKwAEcZarjJTl2iCSEeZI4VYaBAT6tqVGWbV9kNoCtm2lcgy2BWKR87YjuGxkmUH4Cb9ubAbXNc_TgWFXeg1Xo3b33KJP7WMMBFIO7fBaQhj7rgVGp1XXhll6IMcv-AGolFU0PInur7B4ncCSLbue4riEjfi67JXMkOSSAcaZAo_0o1dpbSLgQ', $jwt->signature());
    }

    public function testExceptionIsThrownWhenTryingToSignOpenSSLWithBadKeyType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenSSL failed to sign token');

        $jwt = new JWT(
            new Header(),
            new Payload([
                'iat' => '1665409277',
                'hello' => 'world',
            ])
        );

        $jwt->sign('5ecr3tKeY');
    }

    public function testExceptionIsThrownWhenTryingToSignHMACWithBadKeyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Key must be a string when using hmac encryption');

        $jwt = new JWT(
            new Header(alg: Header::ALG_HS256),
            new Payload([
                'iat' => '1665409277',
                'hello' => 'world',
            ])
        );

        $jwt->sign(openssl_pkey_get_private(
            file_get_contents(sprintf('%s/.stubs/certs/private.pem', dirname(__DIR__, 5))),
            'secret')
        );
    }

    public function testJWTStringRepresentation(): void
    {
        $jwt = new JWT(
            new Header(),
            new Payload([
                'iat' => '1665409277',
                'hello' => 'world',
            ])
        );

        $jwt->sign(openssl_pkey_get_private(
            file_get_contents(sprintf('%s/.stubs/certs/private.pem', dirname(__DIR__, 5))),
            'secret')
        );

        $this->assertEquals('eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOiIxNjY1NDA5Mjc3IiwiaGVsbG8iOiJ3b3JsZCJ9.fv0_h1g4O_OzHOCGbT9biPSwSNiaIKaUqjRSnzZwq4ojXIkG_9cyxW4ac4oLpi3rutT4RszSGw-jKotOT_tah8UESkGZBlVCtKz0KkuVbDacEO4UMOwizSvX8suY31Nw_4TvKciQOmi_XEBAvEDmfo0IaYRTPro0IKwAEcZarjJTl2iCSEeZI4VYaBAT6tqVGWbV9kNoCtm2lcgy2BWKR87YjuGxkmUH4Cb9ubAbXNc_TgWFXeg1Xo3b33KJP7WMMBFIO7fBaQhj7rgVGp1XXhll6IMcv-AGolFU0PInur7B4ncCSLbue4riEjfi67JXMkOSSAcaZAo_0o1dpbSLgQ', (string) $jwt);

        $jwt = new JWT(
            new Header(alg: Header::ALG_HS256),
            new Payload([
                'iat' => '1665409277',
                'hello' => 'world',
            ])
        );

        $jwt->sign('secret');

        $this->assertEquals('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOiIxNjY1NDA5Mjc3IiwiaGVsbG8iOiJ3b3JsZCJ9.OWVkNTZkNTI2YTZmYjEwYzQ0NzYwZTlmMTZjYjMwNzYwM2YwN2Y1ZGJiOGJkNDIzNWY1M2FkNzE1NjdiOGU3Mw', (string) $jwt);
    }

    public function testJWTExpansion(): void
    {
        $jwt = JWT::expand('eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOiIxNjY1NDA5Mjc3IiwiaGVsbG8iOiJ3b3JsZCJ9.fv0_h1g4O_OzHOCGbT9biPSwSNiaIKaUqjRSnzZwq4ojXIkG_9cyxW4ac4oLpi3rutT4RszSGw-jKotOT_tah8UESkGZBlVCtKz0KkuVbDacEO4UMOwizSvX8suY31Nw_4TvKciQOmi_XEBAvEDmfo0IaYRTPro0IKwAEcZarjJTl2iCSEeZI4VYaBAT6tqVGWbV9kNoCtm2lcgy2BWKR87YjuGxkmUH4Cb9ubAbXNc_TgWFXeg1Xo3b33KJP7WMMBFIO7fBaQhj7rgVGp1XXhll6IMcv-AGolFU0PInur7B4ncCSLbue4riEjfi67JXMkOSSAcaZAo_0o1dpbSLgQ');

        $this->assertEquals('world', $jwt->payload['hello']);
    }

    public function testJWTExpansionThrowsExceptionOnInvalidString(): void
    {
        $this->expectException(InvalidJWTException::class);
        $this->expectExceptionMessage('"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOiIxNjY1NDA5Mjc3IiwiaGVsbG8iOiJ3b3JsZCJ9" is not a valid JWT string');

        JWT::expand('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOiIxNjY1NDA5Mjc3IiwiaGVsbG8iOiJ3b3JsZCJ9');
    }

    public function testJWTOpenSSLVerification(): void
    {
        $jwt = JWT::expand('eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOiIxNjY1NDA5Mjc3IiwiaGVsbG8iOiJ3b3JsZCJ9.fv0_h1g4O_OzHOCGbT9biPSwSNiaIKaUqjRSnzZwq4ojXIkG_9cyxW4ac4oLpi3rutT4RszSGw-jKotOT_tah8UESkGZBlVCtKz0KkuVbDacEO4UMOwizSvX8suY31Nw_4TvKciQOmi_XEBAvEDmfo0IaYRTPro0IKwAEcZarjJTl2iCSEeZI4VYaBAT6tqVGWbV9kNoCtm2lcgy2BWKR87YjuGxkmUH4Cb9ubAbXNc_TgWFXeg1Xo3b33KJP7WMMBFIO7fBaQhj7rgVGp1XXhll6IMcv-AGolFU0PInur7B4ncCSLbue4riEjfi67JXMkOSSAcaZAo_0o1dpbSLgQ');

        $this->assertTrue($jwt->verify(openssl_pkey_get_public(file_get_contents(sprintf('%s/.stubs/certs/public.pem', dirname(__DIR__, 5))))));
    }

    public function testJWTHMACVerification(): void
    {
        $jwt = JWT::expand('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOiIxNjY1NDA5Mjc3IiwiaGVsbG8iOiJ3b3JsZCJ9.OWVkNTZkNTI2YTZmYjEwYzQ0NzYwZTlmMTZjYjMwNzYwM2YwN2Y1ZGJiOGJkNDIzNWY1M2FkNzE1NjdiOGU3Mw');

        $this->assertTrue($jwt->verify('secret'));
    }

    public function testJWTVerifyThrowsExceptionOnFakedPayload(): void
    {
        $this->expectException(InvalidJWTException::class);
        $this->expectExceptionMessage('Invalid token signature');

        $fakedPayload = urlsafe_base64_encode(json_encode(['iat' => time(), 'hello' => 'everybody']));

        $jwt = JWT::expand(sprintf('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.%s.OWVkNTZkNTI2YTZmYjEwYzQ0NzYwZTlmMTZjYjMwNzYwM2YwN2Y1ZGJiOGJkNDIzNWY1M2FkNzE1NjdiOGU3Mw', $fakedPayload));

        $jwt->verify('secret');
    }
}
