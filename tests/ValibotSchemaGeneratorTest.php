<?php

namespace Sandikodev\LaravelZodTransformer\Tests;

use PHPUnit\Framework\TestCase;
use Sandikodev\LaravelZodTransformer\Generators\ValibotSchemaGenerator;
use Sandikodev\LaravelZodTransformer\Parsers\LaravelRuleParser;

class ValibotSchemaGeneratorTest extends TestCase
{
    private LaravelRuleParser $parser;
    private ValibotSchemaGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new LaravelRuleParser();
        $this->generator = new ValibotSchemaGenerator();
    }

    public function test_generates_valid_valibot_schema(): void
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

        $this->assertStringContainsString('export const storeStudentSchema = v.object({', $output);
        $this->assertStringContainsString('name: v.pipe(v.string(), v.minLength(1, "Nama wajib diisi."), v.maxLength(100)),', $output);
        $this->assertStringContainsString('v.nullable(v.pipe(v.string(), v.email(), v.maxLength(255)))', $output);
        $this->assertStringContainsString('export type StoreStudentFormData = v.InferOutput<typeof storeStudentSchema>;', $output);
    }
}
