<?php

namespace Sandikodev\LaravelZodTransformer\Parsers;

use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Enum;
use Sandikodev\LaravelZodTransformer\Contracts\RuleParserInterface;
use UnitEnum;

class LaravelRuleParser implements RuleParserInterface
{
    /**
     * @param array<string, mixed> $rules
     * @param array<string, string> $messages
     * @return array<string, RuleDefinition>
     */
    public function parse(array $rules, array $messages = []): array
    {
        $definitions = [];

        foreach ($rules as $field => $rawRule) {
            $definitions[$field] = $this->parseFieldRules($field, $rawRule, $messages);
        }

        return $definitions;
    }

    /**
     * @param string $field
     * @param mixed $rawRule
     * @param array<string, string> $messages
     * @return RuleDefinition
     */
    protected function parseFieldRules(string $field, mixed $rawRule, array $messages): RuleDefinition
    {
        $ruleList = is_string($rawRule) ? explode('|', $rawRule) : (array) $rawRule;

        $type = 'string';
        $isRequired = false;
        $isNullable = false;
        $isOptional = true;
        $isEmail = false;
        $isInteger = false;
        $isUuid = false;
        $isUrl = false;
        $isIp = false;
        $min = null;
        $max = null;
        $length = null;
        $enumValues = null;
        $regex = null;
        $arrayElementType = 'any';

        // Extract field-specific messages
        $fieldMessages = [];
        foreach ($messages as $msgKey => $msgVal) {
            if (str_starts_with($msgKey, $field . '.')) {
                $ruleName = substr($msgKey, strlen($field) + 1);
                $fieldMessages[$ruleName] = $msgVal;
            }
        }

        foreach ($ruleList as $rule) {
            if (is_string($rule)) {
                $parts = explode(':', $rule, 2);
                $ruleName = strtolower(trim($parts[0]));
                $ruleParam = $parts[1] ?? null;

                match ($ruleName) {
                    'required' => [$isRequired = true, $isOptional = false],
                    'nullable' => [$isNullable = true],
                    'sometimes' => [$isOptional = true],
                    'string' => [$type = $type === 'any' ? 'string' : $type],
                    'numeric' => [$type = 'number'],
                    'integer', 'int' => [$type = 'number', $isInteger = true],
                    'boolean', 'bool' => [$type = 'boolean'],
                    'email' => [$type = 'string', $isEmail = true],
                    'date', 'date_format' => [$type = 'date'],
                    'uuid' => [$type = 'string', $isUuid = true],
                    'url' => [$type = 'string', $isUrl = true],
                    'ip', 'ipv4', 'ipv6' => [$type = 'string', $isIp = true],
                    'array' => [$type = 'array'],
                    'file', 'image' => [$type = 'any'],
                    'min' => $ruleParam !== null ? ($min = (float) $ruleParam) : null,
                    'max' => $ruleParam !== null ? ($max = (float) $ruleParam) : null,
                    'size', 'digits' => $ruleParam !== null ? ($length = (int) $ruleParam) : null,
                    'in' => $ruleParam !== null ? [
                        $type = 'enum',
                        $enumValues = array_map('trim', explode(',', $ruleParam)),
                    ] : null,
                    'regex' => $ruleParam !== null ? [
                        $type = 'string',
                        $regex = trim($ruleParam, '/'),
                    ] : null,
                    default => null, // ignore DB constraints like exists, unique, etc.
                };
            } elseif ($rule instanceof In) {
                $type = 'enum';
                // Use reflection or string casting for In rule values
                $ruleStr = (string) $rule;
                if (str_starts_with($ruleStr, 'in:')) {
                    $valStr = substr($ruleStr, 3);
                    $enumValues = array_map(fn($v) => trim($v, '"\' '), explode(',', $valStr));
                }
            } elseif ($rule instanceof Enum) {
                $type = 'enum';
                $enumClass = $this->extractEnumClass($rule);
                if ($enumClass && is_subclass_of($enumClass, UnitEnum::class)) {
                    $enumValues = array_map(
                        fn($case) => property_exists($case, 'value') ? $case->value : $case->name,
                        $enumClass::cases()
                    );
                }
            }
        }

        // Default type resolution: if integer or number, keep it; if not specified, default to string
        if ($isRequired) {
            $isOptional = false;
        }

        return new RuleDefinition(
            fieldName: $field,
            type: $type,
            isRequired: $isRequired,
            isNullable: $isNullable,
            isOptional: $isOptional,
            isEmail: $isEmail,
            isInteger: $isInteger,
            isUuid: $isUuid,
            isUrl: $isUrl,
            isIp: $isIp,
            min: $min,
            max: $max,
            length: $length,
            enumValues: $enumValues,
            regex: $regex,
            arrayElementType: $arrayElementType,
            messages: $fieldMessages,
        );
    }

    /**
     * Helper to extract Enum class from Illuminate Enum rule
     */
    protected function extractEnumClass(Enum $enumRule): ?string
    {
        try {
            $ref = new \ReflectionClass($enumRule);
            if ($ref->hasProperty('type')) {
                $prop = $ref->getProperty('type');
                $prop->setAccessible(true);
                return $prop->getValue($enumRule);
            }
        } catch (\Throwable) {
            // Ignore reflection errors
        }

        return null;
    }
}
