<?php

namespace Sandikodev\LaravelZodTransformer\Collectors;

use Illuminate\Foundation\Http\FormRequest;
use ReflectionClass;
use Sandikodev\LaravelZodTransformer\Attributes\IgnoreZod;
use Sandikodev\LaravelZodTransformer\Attributes\ZodSchema;
use SplFileInfo;

class FormRequestCollector
{
    /**
     * Scan directories and collect FormRequest classes with their rules and messages.
     *
     * @param array<string> $paths
     * @param array<string> $ignorePatterns
     * @return array<string, array{class: string, rules: array, messages: array, schemaName: string}>
     */
    public function collect(array $paths, array $ignorePatterns = []): array
    {
        $collected = [];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $className = $this->extractClassNameFromFile($file->getRealPath());
                if (!$className || !class_exists($className)) {
                    continue;
                }

                if ($this->shouldIgnore($className, $ignorePatterns)) {
                    continue;
                }

                $ref = new ReflectionClass($className);
                if (!$ref->isSubclassOf(FormRequest::class) || $ref->isAbstract()) {
                    continue;
                }

                // Check IgnoreZod attribute
                if (!empty($ref->getAttributes(IgnoreZod::class))) {
                    continue;
                }

                $rulesAndMessages = $this->extractRulesAndMessages($ref);
                if ($rulesAndMessages === null) {
                    continue;
                }

                $schemaName = $this->determineSchemaName($ref);

                $collected[$className] = [
                    'class' => $className,
                    'rules' => $rulesAndMessages['rules'],
                    'messages' => $rulesAndMessages['messages'],
                    'schemaName' => $schemaName,
                ];
            }
        }

        return $collected;
    }

    /**
     * @param ReflectionClass<FormRequest> $ref
     * @return array{rules: array, messages: array}|null
     */
    protected function extractRulesAndMessages(ReflectionClass $ref): ?array
    {
        try {
            /** @var FormRequest $instance */
            $instance = $ref->newInstanceWithoutConstructor();

            if (function_exists('app') && method_exists($instance, 'setContainer')) {
                $instance->setContainer(app());
                if (method_exists($instance, 'initialize')) {
                    $instance->initialize();
                }
            }

            $rules = [];
            if ($ref->hasMethod('rules')) {
                $rulesMethod = $ref->getMethod('rules');
                $rulesMethod->setAccessible(true);
                $rules = (array) $rulesMethod->invoke($instance);
            }

            $messages = [];
            if ($ref->hasMethod('messages')) {
                $messagesMethod = $ref->getMethod('messages');
                $messagesMethod->setAccessible(true);
                $messages = (array) $messagesMethod->invoke($instance);
            }

            return [
                'rules' => $rules,
                'messages' => $messages,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param ReflectionClass<FormRequest> $ref
     */
    protected function determineSchemaName(ReflectionClass $ref): string
    {
        // Check ZodSchema attribute first
        $attrs = $ref->getAttributes(ZodSchema::class);
        if (!empty($attrs)) {
            /** @var ZodSchema $attrInstance */
            $attrInstance = $attrs[0]->newInstance();
            if (!empty($attrInstance->name)) {
                return $attrInstance->name;
            }
        }

        $shortName = $ref->getShortName();

        // Strip Request / FormRequest suffix
        if (str_ends_with($shortName, 'FormRequest')) {
            $shortName = substr($shortName, 0, -11);
        } elseif (str_ends_with($shortName, 'Request')) {
            $shortName = substr($shortName, 0, -7);
        }

        return lcfirst($shortName) . 'Schema';
    }

    protected function shouldIgnore(string $className, array $ignorePatterns): bool
    {
        foreach ($ignorePatterns as $pattern) {
            if (fnmatch($pattern, $className)) {
                return true;
            }
        }
        return false;
    }

    protected function extractClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $namespace = null;
        $class = null;

        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = trim($matches[1]);
        }

        if (preg_match('/class\s+([^\s{]+)/', $content, $matches)) {
            $class = trim($matches[1]);
        }

        if ($namespace && $class) {
            return $namespace . '\\' . $class;
        }

        return null;
    }
}
