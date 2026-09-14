<?php

namespace Sandikodev\LaravelZodTransformer\Parsers;

class RuleDefinition
{
    /**
     * @param array<string, RuleDefinition> $children
     */
    public function __construct(
        public string $fieldName,
        public string $type = 'string', // 'string' | 'number' | 'boolean' | 'date' | 'array' | 'object' | 'any' | 'enum'
        public bool $isRequired = false,
        public bool $isNullable = false,
        public bool $isOptional = true,
        public bool $isEmail = false,
        public bool $isInteger = false,
        public bool $isUuid = false,
        public bool $isUrl = false,
        public bool $isIp = false,
        public bool $isObject = false,
        public bool $isArrayOfObjects = false,
        public ?float $min = null,
        public ?float $max = null,
        public ?int $length = null,
        public ?array $enumValues = null,
        public ?string $regex = null,
        public ?string $arrayElementType = 'any',
        public array $messages = [],
        public ?string $description = null,
        public array $children = [],
    ) {}

    protected function quote(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function toZodExpression(int $indent = 1, bool $coerce = false): string
    {
        $indentStr = str_repeat('    ', $indent);
        $childIndentStr = str_repeat('    ', $indent + 1);

        // If this field is an array of objects
        if ($this->isArrayOfObjects && !empty($this->children)) {
            $childLines = [];
            foreach ($this->children as $childField => $childDef) {
                $childLines[] = sprintf('%s%s: %s,', $childIndentStr, $this->formatFieldName($childField), $childDef->toZodExpression($indent + 1, $coerce));
            }
            $expr = sprintf("z.array(z.object({\n%s\n%s}))", implode("\n", $childLines), $indentStr);
            if ($this->isNullable) $expr .= '.nullable()';
            if ($this->isOptional) $expr .= '.optional()';
            return $expr;
        }

        // If this field is a nested object
        if ($this->isObject && !empty($this->children)) {
            $childLines = [];
            foreach ($this->children as $childField => $childDef) {
                $childLines[] = sprintf('%s%s: %s,', $childIndentStr, $this->formatFieldName($childField), $childDef->toZodExpression($indent + 1, $coerce));
            }
            $expr = sprintf("z.object({\n%s\n%s})", implode("\n", $childLines), $indentStr);
            if ($this->isNullable) $expr .= '.nullable()';
            if ($this->isOptional) $expr .= '.optional()';
            return $expr;
        }

        // Base Zod type expression
        $expr = match ($this->type) {
            'number' => $coerce 
                ? ($this->isInteger ? 'z.coerce.number().int()' : 'z.coerce.number()')
                : ($this->isInteger ? 'z.number().int()' : 'z.number()'),
            'boolean' => $coerce ? 'z.coerce.boolean()' : 'z.boolean()',
            'date' => $coerce ? 'z.coerce.date()' : 'z.string().date()',
            'enum' => !empty($this->enumValues) 
                ? 'z.enum([' . implode(', ', array_map(fn($v) => $this->quote((string) $v), $this->enumValues)) . '])'
                : 'z.string()',
            'array' => 'z.array(' . match($this->arrayElementType) {
                'string' => 'z.string()',
                'number' => $coerce ? 'z.coerce.number()' : 'z.number()',
                'boolean' => $coerce ? 'z.coerce.boolean()' : 'z.boolean()',
                default => 'z.any()',
            } . ')',
            'any' => 'z.any()',
            default => 'z.string()',
        };

        // If string with email
        if ($this->type === 'string' && $this->isEmail) {
            $msg = $this->messages['email'] ?? null;
            $expr .= $msg ? sprintf('.email(%s)', $this->quote($msg)) : '.email()';
        }

        // If string with URL
        if ($this->type === 'string' && $this->isUrl) {
            $msg = $this->messages['url'] ?? null;
            $expr .= $msg ? sprintf('.url(%s)', $this->quote($msg)) : '.url()';
        }

        // If string with UUID
        if ($this->type === 'string' && $this->isUuid) {
            $msg = $this->messages['uuid'] ?? null;
            $expr .= $msg ? sprintf('.uuid(%s)', $this->quote($msg)) : '.uuid()';
        }

        // If string with Regex
        if ($this->type === 'string' && $this->regex) {
            $msg = $this->messages['regex'] ?? null;
            $expr .= $msg ? sprintf('.regex(/%s/, %s)', $this->regex, $this->quote($msg)) : sprintf('.regex(/%s/)', $this->regex);
        }

        // Required check & min length for strings / min value for numbers
        if ($this->isRequired) {
            $requiredMsg = $this->messages['required'] ?? null;

            if ($this->type === 'string') {
                $minLen = $this->min !== null ? (int) $this->min : 1;
                $expr .= $requiredMsg
                    ? sprintf('.min(%s, %s)', $minLen, $this->quote($requiredMsg))
                    : sprintf('.min(%s)', $minLen);
            } elseif ($this->type === 'number') {
                if ($this->min !== null) {
                    $minMsg = $this->messages['min'] ?? null;
                    $expr .= $minMsg
                        ? sprintf('.min(%s, %s)', $this->min, $this->quote($minMsg))
                        : sprintf('.min(%s)', $this->min);
                }
            } elseif ($this->type === 'array') {
                $minItems = $this->min !== null ? (int) $this->min : 1;
                $minMsg = $this->messages['min'] ?? $this->messages['required'] ?? null;
                $expr .= $minMsg
                    ? sprintf('.min(%s, %s)', $minItems, $this->quote($minMsg))
                    : sprintf('.min(%s)', $minItems);
            }
        } else {
            // Optional min rule for strings, numbers, or arrays
            if ($this->min !== null && in_array($this->type, ['string', 'number', 'array'])) {
                $minMsg = $this->messages['min'] ?? null;
                $expr .= $minMsg
                    ? sprintf('.min(%s, %s)', $this->min, $this->quote($minMsg))
                    : sprintf('.min(%s)', $this->min);
            }
        }

        // Max constraint for strings, numbers, or arrays
        if ($this->max !== null && in_array($this->type, ['string', 'number', 'array'])) {
            $maxMsg = $this->messages['max'] ?? null;
            $expr .= $maxMsg
                ? sprintf('.max(%s, %s)', $this->max, $this->quote($maxMsg))
                : sprintf('.max(%s)', $this->max);
        }

        // Exact length
        if ($this->length !== null && $this->type === 'string') {
            $lenMsg = $this->messages['size'] ?? $this->messages['length'] ?? null;
            $expr .= $lenMsg
                ? sprintf('.length(%s, %s)', $this->length, $this->quote($lenMsg))
                : sprintf('.length(%s)', $this->length);
        }

        // Nullable modifier
        if ($this->isNullable) {
            $expr .= '.nullable()';
        }

        // Optional modifier
        if ($this->isOptional) {
            $expr .= '.optional()';
        }

        // If description exists
        if ($this->description) {
            $expr .= sprintf('.describe(%s)', $this->quote($this->description));
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
