<?php

namespace Sandikodev\LaravelZodTransformer\Contracts;

use Sandikodev\LaravelZodTransformer\Parsers\RuleDefinition;

interface RuleParserInterface
{
    /**
     * Parse raw Laravel validation rules and custom messages into structured RuleDefinition objects.
     *
     * @param array<string, mixed> $rules
     * @param array<string, string> $messages
     * @return array<string, RuleDefinition>
     */
    public function parse(array $rules, array $messages = []): array;
}
