# Architecture of API.Shop

API.Shop follows the principles of Clean Architecture and Domain-Driven Design to create a maintainable, flexible e-commerce platform.

## Clean Architecture Layers

The application is structured in the following layers, from innermost to outermost:

### Domain Layer

- Contains business entities, value objects, and domain services
- Defines interfaces that will be implemented by outer layers
- Has no dependencies on frameworks or external libraries (except Webmozart Assert for validation)
- Encapsulates the core business rules and logic

### Application Layer

- Contains use cases and application services
- Orchestrates the flow of data to and from domain entities
- Contains no business rules, just coordination logic
- Depends only on the domain layer

### Infrastructure Layer

- Implements interfaces defined in the domain layer
- Contains adapters for databases, external services, etc.
- Framework-specific implementations (Symfony, PDO, etc.)
- Responsible for persistence, communication with external systems

### UI Layer

- Contains controllers, commands, and API endpoints
- Handles input/output formatting
- Transforms incoming requests to application layer input
- Presents application layer output to users

## Bounded Contexts

The project is divided into bounded contexts, each representing a distinct business domain:

### Content Context

Manages all content-related functionality including:
- Homepage
- Product descriptions
- Content management

### Shared Context

Contains cross-cutting concerns used across all contexts:
- Database migration system
- Common infrastructure components
- Shared command abstractions

## Directory Structure

Within each bounded context, code is organized by layer:

```
src/
├── Content/               # Content Bounded Context
│   ├── Domain/            # Domain layer
│   ├── Application/       # Application layer
│   ├── Infrastructure/    # Infrastructure layer
│   └── UI/                # User Interface layer
└── Shared/                # Shared Bounded Context
    ├── Domain/            # Domain layer
    ├── Application/       # Application layer
    ├── Infrastructure/    # Infrastructure layer
    └── UI/                # User Interface layer
```

## Dependency Flow

Dependencies flow inward:
- Domain layer has no external dependencies
- Application layer depends only on Domain
- Infrastructure and UI layers depend on Application and Domain

This ensures that the core business logic remains isolated from external concerns and can be tested independently.