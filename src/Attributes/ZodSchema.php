<?php

namespace Sandikodev\LaravelZodTransformer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ZodSchema
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
    ) {}
}
