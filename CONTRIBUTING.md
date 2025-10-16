# Contributing to Skald PHP SDK

Thank you for your interest in contributing to the Skald PHP SDK! This document provides guidelines for contributing to the project.

## Getting Started

1. Fork the repository
2. Clone your fork locally
3. Install dependencies: `composer install`
4. Create a new branch for your feature: `git checkout -b feature/my-new-feature`

## Development Setup

### Requirements
- PHP 8.1 or higher
- Composer
- A Skald API key for testing

### Install Dependencies

```bash
composer install
```

### Environment Setup

For running tests, set your API key:

```bash
export SKALD_API_KEY=sk_proj_your_api_key
```

## Code Standards

This project follows strict code quality standards:

### PSR-12 Code Style

All code must adhere to PSR-12 coding standards.

Check code style:
```bash
composer cs-check
```

Fix code style automatically:
```bash
composer cs-fix
```

### Static Analysis

We use PHPStan at level 8 for static analysis.

Run static analysis:
```bash
composer phpstan
```

### Type Safety

- Use strict types (`declare(strict_types=1);`) in all PHP files
- Use type hints for all parameters and return types
- Use readonly properties where appropriate
- Leverage PHP 8.1+ features (enums, union types, etc.)

## Testing

### Running Tests

Run all tests:
```bash
composer test
```

Run specific test file:
```bash
vendor/bin/phpunit tests/SkaldTest.php
```

Run with coverage:
```bash
vendor/bin/phpunit --coverage-html coverage
```

### Writing Tests

- Write tests for all new features
- Maintain or improve code coverage
- Use descriptive test method names
- Include both unit tests and integration tests
- Mock external dependencies where appropriate

Test file naming:
- Unit tests: `tests/ClassName/MethodTest.php`
- Integration tests: `tests/ClassNameTest.php`

## Making Changes

### Commit Messages

Follow conventional commit format:

```
type(scope): subject

body

footer
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

Example:
```
feat(search): add support for custom result ordering

Added optional 'orderBy' parameter to SearchRequest to allow
sorting results by different fields.

Closes #123
```

### Pull Request Process

1. Update documentation for any API changes
2. Add/update tests for your changes
3. Ensure all tests pass
4. Ensure code style checks pass
5. Update CHANGELOG.md with your changes
6. Create a pull request with a clear description

### PR Checklist

- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] Code style checks pass
- [ ] Static analysis passes
- [ ] CHANGELOG.md updated
- [ ] No breaking changes (or clearly documented)

## Documentation

### Code Documentation

- Add PHPDoc comments to all classes, methods, and properties
- Include `@param` and `@return` tags
- Add usage examples in doc blocks for complex methods
- Keep documentation up to date with code changes

### README Updates

Update README.md when:
- Adding new features
- Changing public APIs
- Adding new examples
- Updating requirements

### Examples

When adding new features:
- Add example usage to the `examples/` directory
- Update `examples/README.md` with the new example
- Ensure examples run without errors

## Versioning

We use [Semantic Versioning](https://semver.org/):

- MAJOR: Incompatible API changes
- MINOR: Backwards-compatible new features
- PATCH: Backwards-compatible bug fixes


## Reporting Issues

### Bug Reports

Include:
- PHP version
- Skald SDK version
- Steps to reproduce
- Expected behavior
- Actual behavior
- Error messages/stack traces

### Feature Requests

Include:
- Use case description
- Proposed solution
- Alternative solutions considered
- Willingness to contribute

## License

By contributing, you agree that your contributions will be licensed under the MIT License.


