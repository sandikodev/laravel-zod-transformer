<?php

namespace Sandikodev\LaravelZodTransformer\Generators;

use Sandikodev\LaravelZodTransformer\Contracts\SchemaGeneratorInterface;
use Sandikodev\LaravelZodTransformer\Parsers\RuleDefinition;

class ValibotSchemaGenerator implements SchemaGeneratorInterface
{
    /**
     * @param string $schemaName
     * @param array<string, RuleDefinition> $fieldDefinitions
     * @param array<string, mixed> $options
     * @return string
     */
    public function generate(string $schemaName, array $fieldDefinitions, array $options = []): string
    {
        $generateTypes = $options['generate_inferred_types'] ?? true;
        $typePrefix = $options['type_name'] ?? ucfirst($schemaName);
        if (str_ends_with($typePrefix, 'Schema')) {
            $typePrefix = substr($typePrefix, 0, -6);
        }

        $lines = [];
        $lines[] = sprintf('export const %s = v.object({', $schemaName);

        foreach ($fieldDefinitions as $field => $def) {
            $valiCode = $this->toValibotExpression($def);
            $lines[] = sprintf('    %s: %s,', $this->formatFieldName($field), $valiCode);
        }

        $lines[] = '});';

        if ($generateTypes) {
            $typeName = sprintf('%sFormData', ucfirst($typePrefix));
            $lines[] = '';
            $lines[] = sprintf('export type %s = v.InferOutput<typeof %s>;', $typeName, $schemaName);
        }

        return implode("\n", $lines);
    }

    protected function toValibotExpression(RuleDefinition $def, int $indent = 1): string
    {
        $indentStr = str_repeat('    ', $indent);
        $childIndentStr = str_repeat('    ', $indent + 1);

        if ($def->isArrayOfObjects && !empty($def->children)) {
            $childLines = [];
            foreach ($def->children as $childField => $childDef) {
                $childLines[] = sprintf('%s%s: %s,', $childIndentStr, $this->formatFieldName($childField), $this->toValibotExpression($childDef, $indent + 1));
            }
            $expr = sprintf("v.array(v.object({\n%s\n%s}))", implode("\n", $childLines), $indentStr);
            if ($def->isNullable) $expr = sprintf('v.nullable(%s)', $expr);
            if ($def->isOptional) $expr = sprintf('v.optional(%s)', $expr);
            return $expr;
        }

        if ($def->isObject && !empty($def->children)) {
            $childLines = [];
            foreach ($def->children as $childField => $childDef) {
                $childLines[] = sprintf('%s%s: %s,', $childIndentStr, $this->formatFieldName($childField), $this->toValibotExpression($childDef, $indent + 1));
            }
            $expr = sprintf("v.object({\n%s\n%s})", implode("\n", $childLines), $indentStr);
            if ($def->isNullable) $expr = sprintf('v.nullable(%s)', $expr);
            if ($def->isOptional) $expr = sprintf('v.optional(%s)', $expr);
            return $expr;
        }

        $pipeParts = [];

        // Base type
        $baseType = match ($def->type) {
            'number' => $def->isInteger ? 'v.pipe(v.number(), v.integer())' : 'v.number()',
            'boolean' => 'v.boolean()',
            'date' => 'v.pipe(v.string(), v.isoDate())',
            'enum' => !empty($def->enumValues)
                ? 'v.picklist([' . implode(', ', array_map(fn($v) => json_encode((string) $v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $def->enumValues)) . '])'
                : 'v.string()',
            'array' => 'v.array(v.any())',
            'any' => 'v.any()',
            default => 'v.string()',
        };

        if ($def->type === 'string') {
            if ($def->isEmail) {
                $msg = $def->messages['email'] ?? null;
                $pipeParts[] = $msg ? sprintf('v.email(%s)', json_encode($msg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) : 'v.email()';
            }
            if ($def->isUrl) {
                $pipeParts[] = 'v.url()';
            }
            if ($def->isUuid) {
                $pipeParts[] = 'v.uuid()';
            }
            if ($def->regex) {
                $pipeParts[] = sprintf('v.regex(/%s/)', $def->regex);
            }
            if ($def->isRequired) {
                $minLen = $def->min !== null ? (int) $def->min : 1;
                $msg = $def->messages['required'] ?? null;
                $pipeParts[] = $msg ? sprintf('v.minLength(%s, %s)', $minLen, json_encode($msg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) : sprintf('v.minLength(%s)', $minLen);
            } elseif ($def->min !== null) {
                $pipeParts[] = sprintf('v.minLength(%s)', (int) $def->min);
            }
            if ($def->max !== null) {
                $msg = $def->messages['max'] ?? null;
                $pipeParts[] = $msg ? sprintf('v.maxLength(%s, %s)', (int) $def->max, json_encode($msg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) : sprintf('v.maxLength(%s)', (int) $def->max);
            }
            if ($def->length !== null) {
                $pipeParts[] = sprintf('v.length(%s)', $def->length);
            }
        } elseif ($def->type === 'number') {
            if ($def->min !== null) {
                $pipeParts[] = sprintf('v.minValue(%s)', $def->min);
            }
            if ($def->max !== null) {
                $pipeParts[] = sprintf('v.maxValue(%s)', $def->max);
            }
        }

        if (!empty($pipeParts)) {
            $expr = sprintf('v.pipe(%s, %s)', $baseType, implode(', ', $pipeParts));
        } else {
            $expr = $baseType;
        }

        if ($def->isNullable) {
            $expr = sprintf('v.nullable(%s)', $expr);
        }
        if ($def->isOptional) {
            $expr = sprintf('v.optional(%s)', $expr);
        }

        return $expr;
    }

    protected function formatFieldName(string $field): string
    {
        if (preg_match('/[^a-zA-Z0-9_]/', $field)) {
            return json_encode($field, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return $field;
    }
}
