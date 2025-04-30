<?php

declare(strict_types=1);

namespace App\Security\Authentication\Infrastructure\ApiPlatform\OpenAPI;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model as OpenAPIModel;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\HttpFoundation\Response;

final readonly class OpenAPIDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openAPI = ($this->decorated)($context);

        $schemas = $openAPI->getComponents()->getSchemas();

        $schemas['JWTTokenResponse'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'access_token' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
            ],
        ]);

        $schemas['Credentials'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'username' => [
                    'type' => 'string',
                    'example' => 'john.doe@example.com',
                ],
                'password' => [
                    'type' => 'string',
                    'example' => 'secret',
                ],
            ],
        ]);

        $openAPI->getPaths()->addPath('/auth/login', new OpenAPIModel\PathItem(
            ref: 'JWT Token',
            post: new OpenAPIModel\Operation(
                operationId: 'postAuthLogin',
                tags: ['Auth'],
                responses: [
                    Response::HTTP_OK => [
                        'description' => 'Retrieves JWT after successful authentication.',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/JWTTokenResponse'],
                            ]
                        ]
                    ],
                    Response::HTTP_BAD_REQUEST => [
                        'description' => 'Invalid credentials provided.',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Message'],
                            ]
                        ]
                    ],
                    Response::HTTP_UNAUTHORIZED => [
                        'description' => 'User not validated.',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Message'],
                            ]
                        ]
                    ],
                ],
                summary: 'Get JWT to authenticate requests',
                requestBody: new OpenAPIModel\RequestBody(
                    description: 'Credentials',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/Credentials'],
                        ]
                    ])
                ),
                security: []
            )
        ));

        return $openAPI;
    }
}
