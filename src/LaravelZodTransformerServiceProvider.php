<?php

namespace Sandikodev\LaravelZodTransformer;

use Illuminate\Support\ServiceProvider;
use Sandikodev\LaravelZodTransformer\Commands\GenerateZodSchemasCommand;

class LaravelZodTransformerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/zod-transformer.php',
            'zod-transformer',
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/zod-transformer.php' => config_path('zod-transformer.php'),
            ], 'zod-transformer-config');

            $this->commands([
                GenerateZodSchemasCommand::class,
            ]);
        }
    }
}
