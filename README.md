# Laravel Zod Transformer

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/sandikodev/laravel-zod-transformer)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Author](https://img.shields.io/badge/author-sandikodev-orange.svg)](https://github.com/sandikodev)

> Seamlessly transform Laravel `FormRequest` validation rules into clean, modern, and type-safe **Zod 4 schemas** for Inertia.js, React, Vue, and Svelte applications.

Created and maintained by **sandikodev** (PT Koneksi Jaringan Indonesia).

---

## 🌟 Why This Exists (The Problem Solved)

In modern monolith full-stack architectures (Laravel + Inertia + TypeScript + React/Vue), developers face a massive **DRY (Don't Repeat Yourself)** problem:

1. You write validation rules in Laravel `FormRequest` (`StoreStudentRequest.php`).
2. You have to manually duplicate the exact same validation rules in frontend Zod files (`student.schema.ts`).
3. Whenever backend rules change, frontend schemas drift out of sync.

**Laravel Zod Transformer** automatically scans your `FormRequest` classes, parses their validation rules and custom error messages, and generates production-ready TypeScript Zod schemas + inferred TypeScript types in milliseconds.

---

## 🚀 Installation

Add the package via Composer (or link via path repository in development):

```bash
composer require sandikodev/laravel-zod-transformer --dev
```

Publish configuration (optional):

```bash
php artisan vendor:publish --tag=zod-transformer-config
```

---

## 🛠️ Usage

Simply run:

```bash
php artisan zod:generate
```

### Example Input (`app/Http/Requests/StoreStudentRequest.php`):

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
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap siswa wajib diisi.',
            'nis.required' => 'NIS siswa wajib diisi.',
        ];
    }
}
```

### Example Generated Output (`resources/js/schemas/generated.zod.ts`):

```typescript
import { z } from "zod";

export const storeStudentSchema = z.object({
    nis: z.string().min(1, "NIS siswa wajib diisi.").max(30),
    name: z.string().min(1, "Nama lengkap siswa wajib diisi.").max(100),
    birth_date: z.string().date().nullable().optional(),
    enrollment_year: z.number().int().min(2000).max(2099),
    status: z.enum(["Active", "Inactive", "Graduated"]).nullable().optional(),
    email: z.string().email().max(100).nullable().optional(),
});

export type StoreStudentFormData = z.infer<typeof storeStudentSchema>;
```

---

## 🧩 Supported Rules Mapping

| Laravel Rule | Zod 4 Output |
|---|---|
| `required` | `.min(1, customMessage)` |
| `nullable` | `.nullable()` |
| `sometimes` | `.optional()` |
| `string` | `z.string()` |
| `numeric` / `integer` | `z.number()` / `z.number().int()` |
| `boolean` | `z.boolean()` |
| `email` | `z.string().email()` |
| `date` / `date_format` | `z.string().date()` |
| `min:X` / `max:Y` | `.min(X).max(Y)` |
| `in:A,B,C` / `Rule::in(...)` | `z.enum(["A", "B", "C"])` |
| `Rule::enum(Status::class)` | `z.enum(["Present", "Late", "Absent"])` |
| `regex:/pattern/` | `.regex(/pattern/)` |
| `uuid` / `url` / `ip` | `z.string().uuid()` / `.url()` / `.ip()` |

---

## 📄 License

MIT License © 2026 sandikodev (PT Koneksi Jaringan Indonesia)
