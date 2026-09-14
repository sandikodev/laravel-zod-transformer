<?php

namespace Sandikodev\LaravelZodTransformer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class IgnoreZod {}
