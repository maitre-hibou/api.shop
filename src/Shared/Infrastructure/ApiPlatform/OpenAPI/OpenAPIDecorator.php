<?php

namespace App\Shared\Infrastructure\ApiPlatform\OpenAPI;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;

final readonly class OpenAPIDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openAPI = ($this->decorated)($context);

        $schemas = $openAPI->getComponents()->getSchemas();

        $schemas['Message'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'message' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
            ],
        ]);

        return $openAPI;
    }
}
