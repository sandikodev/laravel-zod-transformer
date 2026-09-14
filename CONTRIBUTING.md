# Contributing to Laravel Zod Transformer

Thank you for your interest in contributing to **Laravel Zod Transformer**!

We welcome contributions of all kinds: bug fixes, feature requests, documentation improvements, rule parsers, and performance optimizations.

---

## 🛠️ Development Setup

1. **Fork and clone the repository:**
   ```bash
   git clone https://github.com/your-username/laravel-zod-transformer.git
   cd laravel-zod-transformer
   ```

2. **Install Composer dependencies:**
   ```bash
   composer install
   ```

3. **Run the test suite:**
   ```bash
   vendor/bin/phpunit
   ```

---

## 📋 Pull Request Guidelines

1. **Branch Naming**: Use descriptive branch names:
   - `fix/nullable-regex-parsing`
   - `feat/support-nested-array-rules`
   - `docs/update-readme-examples`

2. **Code Style**:
   - Follow PSR-12 and Laravel coding standards.
   - Run linter/formatter before committing.

3. **Tests**:
   - Every new feature or bug fix must include corresponding PHPUnit test cases in `tests/`.
   - Ensure all tests pass with `vendor/bin/phpunit`.

4. **Commit Messages**: Follow conventional commits:
   - `feat: add support for Rule::excludeIf()`
   - `fix: correct min length parsing for numeric fields`
   - `docs: add Inertia.js React form example`

---

## 💡 Adding New Laravel Validation Rules

To add support for a new Laravel validation rule:

1. Open `src/Parsers/LaravelRuleParser.php`.
2. Map the rule to token metadata in `parseFieldRules()`.
3. Open `src/Parsers/RuleDefinition.php` and verify the Zod 4 expression generation.
4. Add comprehensive test cases in `tests/LaravelRuleParserTest.php` and `tests/ZodSchemaGeneratorTest.php`.

---

## 📜 Code of Conduct

Please note that this project is released with a [Contributor Code of Conduct](CODE_OF_CONDUCT.md). By participating in this project you agree to abide by its terms.

---

## 💬 Community & Questions

- Open an [Issue](https://github.com/sandikodev/laravel-zod-transformer/issues) for bug reports and feature ideas.
- Join the discussion in [GitHub Discussions](https://github.com/sandikodev/laravel-zod-transformer/discussions).
