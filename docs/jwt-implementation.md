# JWT Authentication Implementation

This document describes the JWT (JSON Web Token) implementation in the API.Shop application.

## Overview

The application implements JWT authentication according to [RFC7519](https://datatracker.ietf.org/doc/html/rfc7519). JWT tokens are used for stateless authentication of API requests.

The implementation consists of several components:
- JWT token generation and validation
- User authentication
- OpenAPI documentation integration
- Certificate management

## Core Components

### JWT Aggregate

The `JWT` class is the main aggregate that represents a JWT token. It supports:

- Token string representation
- Token expansion from string format
- Signature storage (set through JWTAuthority)

Located at: `src/Security/Authentication/Domain/Aggregate/JWT.php`

### Value Objects

#### Header

The `Header` value object represents the JWT header with:
- `typ`: Token type (fixed to "JWT")
- `alg`: Algorithm for signature (HS256 or RS256)

Located at: `src/Security/Authentication/Domain/ValueObject/Header.php`

#### Payload

The `Payload` value object represents the JWT payload (claims). It:
- Stores claims as an immutable array
- Requires an "iat" (issued at) claim
- Implements ArrayAccess for easy claim retrieval

Located at: `src/Security/Authentication/Domain/ValueObject/Payload.php`

## Infrastructure Components

### JWT Authority

The `JWTAuthority` class is responsible for JWT signing and verification:

- Handles both HMAC-based and RSA-based signing
- Loads private/public keys based on configuration
- Verifies token signatures

Located at: `src/Security/Authentication/Infrastructure/Encryption/JWTAuthority.php`

### Certificate Generation

The `GenerateCertsCommand` (`app:security:generate-certs`) console command:
- Generates public/private key pairs for RSA signing
- Manages certificate storage and replacement
- Supports dry-run and overwrite options

Located at: `src/Security/Authentication/UI/Console/Command/GenerateCertsCommand.php`

#### Command Usage

The command can be executed with the following options:

```bash
# Generate certificates and save them to the configured paths
$ make console c="app:security:generate-certs"

# Preview certificates without writing them to files (dry-run)
$ make console c="app:security:generate-certs --dry-run"

# Force overwrite existing certificates
$ make console c="app:security:generate-certs --overwrite"
```

When executed without options, the command:
1. Checks if certificates already exist (stopping if they do)
2. Generates a new RSA key pair using OpenSSL
3. Saves the keys to the configured locations from the application's JWT config

In dry-run mode, the command will output the generated keys to the console without saving them, which is useful for testing.

The `--overwrite` option forces replacement of existing keys, prompting for confirmation unless run with `--no-interaction`.

#### Generated Files

The command generates two files:
- **Public key**: Used for token verification
- **Private key**: Used for token signing, protected with a passphrase

The file paths are configured in the application's JWT configuration section.

## Usage Example

```php
$jwt = new JWT(
    new Header(alg: Header::ALG_RS256),
    new Payload([
        'iat' => time(),
        'subj' => 'user@example.com',
        'exp' => time() + 3600
    ])
);

// Sign with JWT authority
$jwtAuthority->sign($jwt);

// Verify a JWT token
$receivedJwt = JWT::expand($jwtString);
$isValid = $jwtAuthority->verify($receivedJwt);
```

## Security Considerations

- Private keys should be properly secured and have a passphrase
- Use the `app:security:generate-certs` command to generate secure keys
- The JWT implementation validates signatures to prevent tampering
- All payloads are immutable to prevent modification after creation
