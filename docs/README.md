# API.Shop Documentation

Welcome to the API.Shop documentation. This documentation provides an overview of the project structure, architecture, and development guidelines.

## Table of Contents

- [Architecture](./architecture.md) - Clean architecture implementation details
- [Development Guide](./development-guide.md) - How to set up and develop the project
- [Database Migrations](./database-migrations.md) - How database migrations work
- [Testing](./testing.md) - Testing approach and guidelines

## What is API.Shop?

API.Shop is a headless e-commerce platform built on top of Symfony framework. It's designed as a modern, API-first solution that follows clean architecture principles and Domain-Driven Design (DDD) methodologies.

## Key Features

- **Clean Architecture**: Separation of concerns with layers (Domain, Application, Infrastructure, UI)
- **Domain-Driven Design**: Organized around business domains and bounded contexts
- **API-First**: Built as a headless solution that can power various frontends
- **Modern PHP**: Using PHP 8.4+ features including strict typing, attributes, etc.
- **Database Migrations**: Built-in migration system for database schema evolution
- **Testing**: Comprehensive testing approach including unit, integration and application tests

## Technology Stack

- PHP 8.4+
- Symfony 6.4
- Docker for containerization
- PDO for database connectivity
- PHPUnit for testing
- Webmozart Assert for validation

## Project Structure

The project follows a DDD-inspired structure with bounded contexts:

- **Content**: Manages content-related functionality
- **Shared**: Cross-cutting concerns shared across contexts

Within each context, code is organized into layers:

- **Domain**: Business models and rules (entities, value objects, domain services)
- **Application**: Use cases and application services
- **Infrastructure**: Technical implementations of domain interfaces
- **UI**: User interface components (HTTP controllers, console commands)

This structure helps maintain separation of concerns while keeping related functionality grouped together.
