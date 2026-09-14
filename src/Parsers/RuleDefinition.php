<?php

namespace Sandikodev\LaravelZodTransformer\Parsers;

class RuleDefinition
{
    public function __construct(
        public string $fieldName,
        public string $type = 'string', // 'string' | 'number' | 'boolean' | 'date' | 'array' | 'any' | 'enum'
        public bool $isRequired = false,
        public bool $isNullable = false,
        public bool $isOptional = true,
        public bool $isEmail = false,
        public bool $isInteger = false,
        public bool $isUuid = false,
        public bool $isUrl = false,
        public bool $isIp = false,
        public ?float $min = null,
        public ?float $max = null,
        public ?int $length = null,
        public ?array $enumValues = null,
        public ?string $regex = null,
        public ?string $arrayElementType = 'any',
        public array $messages = [],
        public ?string $description = null,
    ) {}

    protected function quote(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function toZodExpression(): string
    {
        // Base Zod type expression
        $expr = match ($this->type) {
            'number' => $this->isInteger ? 'z.number().int()' : 'z.number()',
            'boolean' => 'z.boolean()',
            'date' => 'z.string().date()',
            'enum' => !empty($this->enumValues) 
                ? 'z.enum([' . implode(', ', array_map(fn($v) => $this->quote((string) $v), $this->enumValues)) . '])'
                : 'z.string()',
            'array' => 'z.array(' . ($this->arrayElementType === 'string' ? 'z.string()' : ($this->arrayElementType === 'number' ? 'z.number()' : 'z.any()')) . ')',
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
                $minLen = $this->min ?? 1;
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
}
