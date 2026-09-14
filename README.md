# Laravel Zod Transformer

<p align="center">
  <img src="https://raw.githubusercontent.com/sandikodev/laravel-zod-transformer/main/art/banner.png" alt="Laravel Zod Transformer Banner" width="800" onerror="this.style.display='none'"/>
</p>

<p align="center">
  <a href="https://github.com/sandikodev/laravel-zod-transformer/actions"><img src="https://github.com/sandikodev/laravel-zod-transformer/workflows/Run%20Tests/badge.svg" alt="Build Status"></a>
  <a href="https://packagist.org/packages/sandikodev/laravel-zod-transformer"><img src="https://img.shields.io/packagist/v/sandikodev/laravel-zod-transformer.svg?style=flat-square" alt="Latest Version on Packagist"></a>
  <a href="https://packagist.org/packages/sandikodev/laravel-zod-transformer"><img src="https://img.shields.io/packagist/dt/sandikodev/laravel-zod-transformer.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square" alt="Software License"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-%3E%3D%208.2-777BB4.svg?style=flat-square&logo=php&logoColor=white" alt="PHP Version"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012%20%7C%2013-FF2D20.svg?style=flat-square&logo=laravel&logoColor=white" alt="Laravel Version"></a>
  <a href="https://zod.dev"><img src="https://img.shields.io/badge/Zod-4.x%20Ready-3E67B1.svg?style=flat-square&logo=typescript&logoColor=white" alt="Zod 4"></a>
</p>

---

## ⚡ The Philosophy & The Problem Solved

In modern monolith full-stack architectures (**Laravel + Inertia.js + React/Vue/Svelte + TypeScript**), developers suffer from a painful **DRY (Don't Repeat Yourself) violation**:

1. You declare validation rules and custom messages on the backend in Laravel `FormRequest` (`StoreStudentRequest.php`).
2. You are forced to **manually duplicate and maintain** the exact same validation rules in frontend Zod files (`student.schema.ts`).
3. While tools like `spatie/laravel-typescript-transformer` generate static TypeScript type definitions (`.d.ts`), these types are **erased at runtime in the browser** and cannot validate user inputs in React/Vue forms.
4. When backend validation rules change, frontend schemas drift out of sync, causing broken UX and unhandled validation errors.

**Laravel Zod Transformer** bridges this gap. It scans your Laravel `FormRequest` classes, parses validation rules and custom error messages, and generates clean, production-ready, type-safe **Zod 4 runtime schemas** and inferred TypeScript types in **milliseconds** (`~18ms`).

---

## 🚀 Key Features

* ✨ **Zero Drift**: Single source of truth in Laravel `FormRequest`.
* 🛡️ **Zod 4 Runtime Ready**: Generates modern `z.object({...})` syntax.
* 💬 **Custom Error Messages Injection**: Automatically embeds messages from `FormRequest::messages()` into Zod error strings.
* 🏷️ **Type Inference Included**: Automatically exports `export type StoreStudentFormData = z.infer<typeof storeStudentSchema>`.
* ⚡ **Blazing Fast**: Compiles dozens of FormRequests in sub-20ms.
* 🎯 **Framework Agnostic on Client**: Works seamlessly with Inertia.js, React, Vue 3, Svelte, TanStack Form, React Hook Form, or vanilla TypeScript.

---

## 📦 Installation

Install the package via Composer:

```bash
composer require sandikodev/laravel-zod-transformer --dev
```

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag=zod-transformer-config
```

---

## 🛠️ Usage

Run the Artisan command to transform your FormRequests:

```bash
php artisan zod:generate
```

### 1. Define your Laravel FormRequest:

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nis' => 'required|string|max:30|unique:students,nis',
            'name' => 'required|string|max:100',
            'birth_date' => 'nullable|date',
            'enrollment_year' => 'required|integer|min:2000|max:2099',
            'status' => 'nullable|in:Active,Inactive,Graduated',
            'email' => 'nullable|email|max:100',
            'password' => 'nullable|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'nis.required' => 'Student ID (NIS) is required.',
            'name.required' => 'Full name is required.',
            'password.min' => 'Password must be at least 6 characters.',
        ];
    }
}
```

### 2. Auto-Generated Output (`resources/js/schemas/generated.zod.ts`):

```typescript
import { z } from "zod";

// ─── storeStudentSchema (StoreStudentRequest) ───
export const storeStudentSchema = z.object({
    nis: z.string().min(1, "Student ID (NIS) is required.").max(30),
    name: z.string().min(1, "Full name is required.").max(100),
    birth_date: z.string().date().nullable().optional(),
    enrollment_year: z.number().int().min(2000).max(2099),
    status: z.enum(["Active", "Inactive", "Graduated"]).nullable().optional(),
    email: z.string().email().max(100).nullable().optional(),
    password: z.string().min(6, "Password must be at least 6 characters.").nullable().optional(),
});

export type StoreStudentFormData = z.infer<typeof storeStudentSchema>;
```

### 3. Consume in React / Inertia Component:

```tsx
import { useForm } from "@inertiajs/react";
import { storeStudentSchema, type StoreStudentFormData } from "@/schemas/generated.zod";

export default function CreateStudent() {
    const form = useForm<StoreStudentFormData>({
        nis: "",
        name: "",
        enrollment_year: 2026,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Instant client-side validation using Zod
        const result = storeStudentSchema.safeParse(form.data);
        if (!result.success) {
            console.error(result.error.format());
            return;
        }

        form.post("/students");
    };

    return (
        <form onSubmit={handleSubmit}>
            {/* Form Fields */}
        </form>
    );
}
```

---

## 🧩 Supported Laravel Validation Rules

| Laravel Rule | Generated Zod 4 Expression |
|---|---|
| `'required'` | `.min(1, customMessage)` |
| `'nullable'` | `.nullable()` |
| `'sometimes'` | `.optional()` |
| `'string'` | `z.string()` |
| `'numeric'` / `'integer'` | `z.number()` / `z.number().int()` |
| `'boolean'` | `z.boolean()` |
| `'email'` | `z.string().email(customMessage)` |
| `'date'` / `'date_format'` | `z.string().date()` |
| `'url'` / `'uuid'` / `'ip'` | `z.string().url()` / `.uuid()` / `.ip()` |
| `'min:X'` / `'max:Y'` | `.min(X).max(Y)` |
| `'size:X'` / `'digits:X'` | `.length(X)` |
| `'in:A,B,C'` / `Rule::in(['A', 'B'])` | `z.enum(["A", "B", "C"])` |
| `Rule::enum(StatusEnum::class)` | `z.enum(["Pending", "Approved", "Rejected"])` |
| `'regex:/^[A-Z]+$/'` | `z.string().regex(/^[A-Z]+/)` |
| `'array'` | `z.array(z.any())` |
| `'file'` / `'image'` | `z.any()` |

---

## ⚙️ Configuration

You can customize scanning paths, naming strategies, and output files in `config/zod-transformer.php`:

```php
return [
    'paths' => [
        app_path('Http/Requests'),
    ],
    'output_file' => resource_path('js/schemas/generated.zod.ts'),
    'naming_strategy' => 'camelCase', // 'camelCase', 'snakeCase'
    'strip_request_suffix' => true,
    'generate_inferred_types' => true,
    'ignore' => [
        'App\Http\Requests\Internal*',
    ],
];
```

---

## 🧪 Testing

```bash
composer test
# or
vendor/bin/phpunit
```

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. Check out our [Contributing Guidelines](CONTRIBUTING.md) for more details.

---

## 📜 Security Vulnerabilities

If you discover any security vulnerabilities within this package, please open a security advisory via [GitHub Security Advisories](https://github.com/sandikodev/laravel-zod-transformer/security/advisories/new).

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

<p align="center">
  Crafted with ❤️ by <a href="https://github.com/sandikodev"><strong>sandikodev</strong></a>
</p>
