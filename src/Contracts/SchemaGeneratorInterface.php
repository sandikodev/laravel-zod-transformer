<?php

namespace Sandikodev\LaravelZodTransformer\Contracts;

use Sandikodev\LaravelZodTransformer\Parsers\RuleDefinition;

interface SchemaGeneratorInterface
{
    /**
     * Generate TypeScript Zod schema code from parsed RuleDefinitions.
     *
     * @param string $schemaName
     * @param array<string, RuleDefinition> $fieldDefinitions
     * @param array<string, mixed> $options
     * @return string
     */
    public function generate(string $schemaName, array $fieldDefinitions, array $options = []): string;
}
