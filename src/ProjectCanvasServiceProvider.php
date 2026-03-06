<?php

namespace Platform\ProjectCanvas;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ProjectCanvasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Canvas-Resolver überschreiben (loose coupling via Core-Contracts)
        $this->app->singleton(
            \Platform\Core\Contracts\CanvasResolverInterface::class,
            fn () => new \Platform\ProjectCanvas\Services\CoreCanvasResolver()
        );
        $this->app->singleton(
            \Platform\Core\Contracts\CanvasOptionsProviderInterface::class,
            fn () => new \Platform\ProjectCanvas\Services\CoreCanvasOptionsProvider()
        );
        $this->app->singleton(
            \Platform\Core\Contracts\CanvasForContextProviderInterface::class,
            fn () => new \Platform\ProjectCanvas\Services\CoreCanvasForContextProvider()
        );
    }

    public function boot(): void
    {
        // Step 1: Load config
        $this->mergeConfigFrom(__DIR__ . '/../config/project-canvas.php', 'project-canvas');
        $this->mergeConfigFrom(__DIR__ . '/../config/pc-templates.php', 'pc-templates');

        // Step 2: Register module
        if (
            config()->has('project-canvas.routing') &&
            config()->has('project-canvas.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key' => 'project-canvas',
                'title' => 'Project Canvas',
                'routing' => config('project-canvas.routing'),
                'guard' => config('project-canvas.guard'),
                'navigation' => config('project-canvas.navigation'),
                'sidebar' => config('project-canvas.sidebar'),
            ]);
        }

        // Step 3: Routes (if module registered)
        if (PlatformCore::getModule('project-canvas')) {
            ModuleRouter::group('project-canvas', function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });
        }

        // Step 4: Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Step 5: Publish config
        $this->publishes([
            __DIR__ . '/../config/project-canvas.php' => config_path('project-canvas.php'),
            __DIR__ . '/../config/pc-templates.php' => config_path('pc-templates.php'),
        ], 'config');

        // Step 6: Views & Livewire
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'project-canvas');
        $this->registerLivewireComponents();

        // Step 7: Tools
        $this->registerTools();
    }

    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            // Overview
            $registry->register(new \Platform\ProjectCanvas\Tools\PcOverviewTool());

            // Canvas CRUD
            $registry->register(new \Platform\ProjectCanvas\Tools\ListCanvasesTool());
            $registry->register(new \Platform\ProjectCanvas\Tools\GetCanvasTool());
            $registry->register(new \Platform\ProjectCanvas\Tools\CreateCanvasTool());
            $registry->register(new \Platform\ProjectCanvas\Tools\UpdateCanvasTool());
            $registry->register(new \Platform\ProjectCanvas\Tools\DeleteCanvasTool());

            // Entry CRUD
            $registry->register(new \Platform\ProjectCanvas\Tools\ListEntriesTool());
            $registry->register(new \Platform\ProjectCanvas\Tools\CreateEntryTool());
            $registry->register(new \Platform\ProjectCanvas\Tools\UpdateEntryTool());
            $registry->register(new \Platform\ProjectCanvas\Tools\DeleteEntryTool());

            // Entry Bulk/Reorder
            $registry->register(new \Platform\ProjectCanvas\Tools\BulkCreateEntriesTool());
            $registry->register(new \Platform\ProjectCanvas\Tools\ReorderEntriesTool());

            // Snapshots
            $registry->register(new \Platform\ProjectCanvas\Tools\CreateSnapshotTool());
            $registry->register(new \Platform\ProjectCanvas\Tools\ListSnapshotsTool());
            $registry->register(new \Platform\ProjectCanvas\Tools\GetSnapshotTool());
            $registry->register(new \Platform\ProjectCanvas\Tools\CompareSnapshotsTool());

            // Utilities
            $registry->register(new \Platform\ProjectCanvas\Tools\ExportCanvasTool());
            $registry->register(new \Platform\ProjectCanvas\Tools\StatusTool());
        } catch (\Throwable $e) {
            \Log::warning('ProjectCanvas: Tool-Registrierung fehlgeschlagen', ['error' => $e->getMessage()]);
        }
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\ProjectCanvas\\Livewire';
        $prefix = 'project-canvas';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }
}
