<?php

namespace Sandikodev\LaravelZodTransformer\Generators;

use Sandikodev\LaravelZodTransformer\Contracts\SchemaGeneratorInterface;
use Sandikodev\LaravelZodTransformer\Parsers\RuleDefinition;

class ZodSchemaGenerator implements SchemaGeneratorInterface
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
        $coerce = $options['coerce'] ?? false;
        $typePrefix = $options['type_name'] ?? ucfirst($schemaName);
        if (str_ends_with($typePrefix, 'Schema')) {
            $typePrefix = substr($typePrefix, 0, -6);
        }

        $lines = [];
        $lines[] = sprintf('export const %s = z.object({', $schemaName);

        foreach ($fieldDefinitions as $field => $def) {
            $zodCode = $def->toZodExpression(1, $coerce);
            $lines[] = sprintf('    %s: %s,', $this->formatFieldName($field), $zodCode);
        }

        $lines[] = '});';

        if ($generateTypes) {
            $typeName = sprintf('%sFormData', ucfirst($typePrefix));
            $lines[] = '';
            $lines[] = sprintf('export type %s = z.infer<typeof %s>;', $typeName, $schemaName);
        }

        return implode("\n", $lines);
    }

    protected function formatFieldName(string $field): string
    {
        if (preg_match('/[^a-zA-Z0-9_]/', $field)) {
            return json_encode($field, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return $field;
    }
}
