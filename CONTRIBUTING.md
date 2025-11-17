# Contributing to FreeOui

Thank you for your interest in contributing to FreeOui! This document provides guidelines and instructions for contributing.

## Code of Conduct

By participating in this project, you agree to maintain a respectful and collaborative environment.

## Getting Started

1. **Fork the repository**
```bash
git clone https://github.com/haythemsaa/freeoui.git
cd freeoui
```

2. **Create a feature branch**
```bash
git checkout -b feature/your-feature-name
```

3. **Set up development environment**
```bash
docker-compose up -d
```

## Development Workflow

### Backend (Laravel)

```bash
cd backend

# Install dependencies
composer install

# Run tests
php artisan test

# Code style check
./vendor/bin/phpcs

# Fix code style
./vendor/bin/phpcbf
```

### Mobile (Flutter)

```bash
cd mobile

# Install dependencies
flutter pub get

# Run tests
flutter test

# Analyze code
flutter analyze
```

### Web Admin (React)

```bash
cd web-admin

# Install dependencies
npm install

# Run tests
npm run test

# Lint code
npm run lint

# Type check
npm run type-check
```

## Commit Message Guidelines

We follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

### Examples

```
feat(auth): add SMS OTP verification

Implement SMS-based OTP verification using Twilio.
Users can now verify their phone number during registration.

Closes #123
```

```
fix(proximity): correct distance calculation

Fix PostGIS query for proximity detection.
Distance was being calculated in degrees instead of meters.

Fixes #456
```

## Pull Request Process

1. **Update documentation** if needed
2. **Add tests** for new features
3. **Ensure all tests pass**
4. **Update CHANGELOG.md** with your changes
5. **Create pull request** with clear description

### PR Title Format

```
[Type] Brief description
```

Examples:
- `[Feature] Add proximity alerts system`
- `[Fix] Resolve QR code validation issue`
- `[Docs] Update API documentation`

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
Describe testing performed

## Screenshots (if applicable)
Add screenshots

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Comments added for complex code
- [ ] Documentation updated
- [ ] Tests added/updated
- [ ] All tests pass
- [ ] No new warnings
```

## Coding Standards

### PHP (Laravel)

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/)
- Use type hints
- Write PHPDoc comments
- Use dependency injection
- Follow SOLID principles

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

class ProximityAlertService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Send proximity alert to user
     *
     * @param User $user
     * @param string $advantageId
     * @return bool
     */
    public function sendAlert(User $user, string $advantageId): bool
    {
        // Implementation
    }
}
```

### TypeScript (React)

- Use functional components
- Use TypeScript strictly
- Follow React best practices
- Use proper naming conventions

```typescript
interface ProximityAlert {
  id: string;
  userId: string;
  advantageId: string;
  distance: number;
}

export const ProximityAlertCard: React.FC<{ alert: ProximityAlert }> = ({ alert }) => {
  return (
    <div className="alert-card">
      {/* Implementation */}
    </div>
  );
};
```

### Dart (Flutter)

- Follow [Effective Dart](https://dart.dev/guides/language/effective-dart)
- Use proper widget organization
- Implement proper state management

```dart
class ProximityAlertCard extends ConsumerWidget {
  final ProximityAlert alert;

  const ProximityAlertCard({
    Key? key,
    required this.alert,
  }) : super(key: key);

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Card(
      // Implementation
    );
  }
}
```

## Testing Guidelines

### Unit Tests

- Test business logic
- Test edge cases
- Mock external dependencies
- Aim for >80% coverage

### Integration Tests

- Test API endpoints
- Test database interactions
- Test third-party integrations

### E2E Tests

- Test critical user flows
- Test on multiple devices/browsers

## Documentation

- Update README.md for major changes
- Add JSDoc/PHPDoc/DartDoc comments
- Update API documentation
- Add examples for new features

## Database Migrations

- Never edit existing migrations
- Create new migration for changes
- Test up and down migrations
- Add rollback support

```php
public function up()
{
    Schema::create('new_table', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('new_table');
}
```

## Questions?

- Open an issue for bugs
- Use discussions for questions
- Contact maintainers for security issues

## License

By contributing, you agree that your contributions will be licensed under the project's license.

Thank you for contributing to FreeOui! 🎉
