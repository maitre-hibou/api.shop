# Development Guide

This guide covers the setup, development workflow, and best practices for contributing to API.Shop.

## Getting Started

### Prerequisites

- Docker and Docker Compose
- Make
- Git

### Setup

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd api.shop
   ```

2. Start the project setup:
   ```bash
   make install
   ```

   This will build the Docker images and start the containers.

### Development Workflow

#### Common Commands

Use the following Make commands to manage your development workflow:

- `make install`: Start project setup (build + up)
- `make build`: Build Docker images
- `make up`: Start Docker containers
- `make down`: Remove Docker containers
- `make composer c="command"`: Run Composer in container
- `make console c="command"`: Run Symfony console command in container

#### Running Symfony Commands

To run Symfony console commands:

```bash
make console c="command"
```

Example:
```bash
make console c="cache:clear"
```

#### Running Composer Commands

To run Composer commands:

```bash
make composer c="command"
```

Example:
```bash
make composer c="require symfony/serializer"
```

## Coding Standards

### PHP Coding Standards

- Use PSR-4 autoloading standards
- Add `declare(strict_types=1);` to all PHP files
- Prefer final classes unless inheritance is needed
- Use PHP 8.4+ features:
  - Constructor property promotion
  - Named arguments
  - Attributes
  - Match expressions
  - Union types
- Use strong type hinting and return types
- Use Webmozart\Assert for parameter validation

### Architecture Guidelines

- Follow Domain-Driven Design structure with bounded contexts
- Organize files by feature/domain rather than by technical layer
- Dependency injection via constructor
- Follow Symfony best practices for controllers and services
- Keep domain layer free from framework dependencies
- Use interfaces for dependencies that cross layer boundaries
