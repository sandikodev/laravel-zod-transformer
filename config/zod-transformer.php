<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Source Paths to Scan
     |--------------------------------------------------------------------------
     |
     | Paths to scan for FormRequest classes that should be transformed
     | into Zod schemas.
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
     | Where the generated TypeScript Zod schemas file should be written.
     |
     */
    'output_file' => resource_path('js/schemas/generated.zod.ts'),

    /*
     |--------------------------------------------------------------------------
     | Schema Naming Strategy
     |--------------------------------------------------------------------------
     |
     | Defines how the generated Zod schema constant names will be derived from
     | the FormRequest class name.
     |
     | Supported: 'camelCase', 'snakeCase', 'kebabCase'
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
     | Include TypeScript Types (z.infer)
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
