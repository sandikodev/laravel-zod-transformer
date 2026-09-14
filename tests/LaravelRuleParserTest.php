<?php

namespace Sandikodev\LaravelZodTransformer\Tests;

use PHPUnit\Framework\TestCase;
use Sandikodev\LaravelZodTransformer\Parsers\LaravelRuleParser;

class LaravelRuleParserTest extends TestCase
{
    private LaravelRuleParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new LaravelRuleParser();
    }

    public function test_parses_string_rules_correctly(): void
    {
        $rules = [
            'name' => 'required|string|max:100',
            'nis' => 'required|string|min:5|max:20',
            'email' => 'nullable|email|max:255',
            'age' => 'required|integer|min:10|max:100',
            'status' => 'nullable|in:active,inactive',
        ];

        $messages = [
            'name.required' => 'Nama wajib diisi.',
            'email.email' => 'Email tidak valid.',
        ];

        $definitions = $this->parser->parse($rules, $messages);

        $this->assertCount(5, $definitions);

        // Name field
        $nameDef = $definitions['name'];
        $this->assertTrue($nameDef->isRequired);
        $this->assertFalse($nameDef->isNullable);
        $this->assertSame('string', $nameDef->type);
        $this->assertSame(100.0, $nameDef->max);
        $this->assertSame('Nama wajib diisi.', $nameDef->messages['required']);

        // NIS field
        $nisDef = $definitions['nis'];
        $this->assertTrue($nisDef->isRequired);
        $this->assertSame(5.0, $nisDef->min);
        $this->assertSame(20.0, $nisDef->max);

        // Email field
        $emailDef = $definitions['email'];
        $this->assertFalse($emailDef->isRequired);
        $this->assertTrue($emailDef->isNullable);
        $this->assertTrue($emailDef->isEmail);
        $this->assertSame('Email tidak valid.', $emailDef->messages['email']);

        // Age field
        $ageDef = $definitions['age'];
        $this->assertTrue($ageDef->isRequired);
        $this->assertSame('number', $ageDef->type);
        $this->assertTrue($ageDef->isInteger);
        $this->assertSame(10.0, $ageDef->min);

        // Status field
        $statusDef = $definitions['status'];
        $this->assertSame('enum', $statusDef->type);
        $this->assertSame(['active', 'inactive'], $statusDef->enumValues);
    }
}
