<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Schema Validator Driver
     |--------------------------------------------------------------------------
     |
     | The target client-side validator library to generate schemas for.
     | Supported: 'zod' (default), 'valibot'
     |
     */
    'driver' => 'zod',

    /*
     |--------------------------------------------------------------------------
     | Source Paths to Scan
     |--------------------------------------------------------------------------
     |
     | Paths to scan for FormRequest classes that should be transformed
     | into validator schemas.
     |
     */
    'paths' => [
        app_path('Http/Requests'),
    ],

    /*
     |--------------------------------------------------------------------------
     | Output Configuration
     |--------------------------------------------------------------------------
     |
     | Where the generated TypeScript validator schemas file should be written.
     |
     */
    'output_file' => resource_path('js/schemas/generated.zod.ts'),

    /*
     |--------------------------------------------------------------------------
     | Automatic Type Coercion (z.coerce)
     |--------------------------------------------------------------------------
     |
     | If true, generates `z.coerce.number()`, `z.coerce.date()`, and `z.coerce.boolean()`
     | to automatically transform HTML form string inputs into their native JavaScript types.
     |
     */
    'coerce' => true,

    /*
     |--------------------------------------------------------------------------
     | Schema Naming Strategy
     |--------------------------------------------------------------------------
     |
     | Defines how the generated schema constant names will be derived from
     | the FormRequest class name.
     |
     | Supported: 'camelCase', 'snakeCase'
     | Example: StoreStudentRequest -> storeStudentSchema (camelCase)
     |
     */
    'naming_strategy' => 'camelCase',

    /*
     |--------------------------------------------------------------------------
     | Strip Request Suffix
     |--------------------------------------------------------------------------
     |
     | If true, suffixes like 'Request' or 'FormRequest' will be stripped before
     | appending 'Schema'.
     | Example: StoreStudentRequest -> storeStudentSchema
     |
     */
    'strip_request_suffix' => true,

    /*
     |--------------------------------------------------------------------------
     | Include TypeScript Inferred Types
     |--------------------------------------------------------------------------
     |
     | If true, exports `export type XFormData = z.infer<typeof xSchema>`
     | alongside each schema.
     |
     */
    'generate_inferred_types' => true,

    /*
     |--------------------------------------------------------------------------
     | Ignore Patterns
     |--------------------------------------------------------------------------
     |
     | Class names or regex patterns to skip during transformation.
     |
     */
    'ignore' => [
        // 'App\Http\Requests\Internal*',
    ],
];
