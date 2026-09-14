<?php

namespace Sandikodev\LaravelZodTransformer\Tests;

use PHPUnit\Framework\TestCase;
use Sandikodev\LaravelZodTransformer\Generators\ZodSchemaGenerator;
use Sandikodev\LaravelZodTransformer\Parsers\LaravelRuleParser;

class ZodSchemaGeneratorTest extends TestCase
{
    private LaravelRuleParser $parser;
    private ZodSchemaGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new LaravelRuleParser();
        $this->generator = new ZodSchemaGenerator();
    }

    public function test_generates_valid_zod_schema(): void
    {
        $rules = [
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'status' => 'nullable|in:Active,Inactive',
            'points' => 'required|integer|min:1',
        ];

        $messages = [
            'name.required' => 'Nama wajib diisi.',
        ];

        $definitions = $this->parser->parse($rules, $messages);
        $output = $this->generator->generate('storeStudentSchema', $definitions, [
            'generate_inferred_types' => true,
        ]);

        $this->assertStringContainsString('export const storeStudentSchema = z.object({', $output);
        $this->assertStringContainsString('name: z.string().min(1, "Nama wajib diisi.").max(100),', $output);
        $this->assertStringContainsString('email: z.string().email().max(255).nullable().optional(),', $output);
        $this->assertStringContainsString('status: z.enum(["Active", "Inactive"]).nullable().optional(),', $output);
        $this->assertStringContainsString('points: z.number().int().min(1),', $output);
        $this->assertStringContainsString('export type StoreStudentFormData = z.infer<typeof storeStudentSchema>;', $output);
    }
}
