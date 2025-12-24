<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionClass;

class AnalyzeProjectUsage extends Command
{
    protected $signature = 'project:usage';
    protected $description = 'Analyze unused routes, controllers, and methods';

    public function handle()
    {
        $this->info("\n=== 🔍 Project Usage Analysis ===\n");

        $routes = Route::getRoutes();
        $usedMethods = [];
        $controllersFound = [];

        foreach ($routes as $route) {
            $action = $route->getActionName();
            if (strpos($action, '@') !== false) {
                [$controller, $method] = explode('@', $action);
                $usedMethods[] = "$controller@$method";
                $controllersFound[] = $controller;
            }
        }

        $controllerFiles = File::allFiles(app_path('Http/Controllers'));
        $unusedMethods = [];
        $unusedControllers = [];

        foreach ($controllerFiles as $file) {
            $className = "App\\Http\\Controllers\\" .
                str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            if (!class_exists($className)) continue;

            $reflection = new ReflectionClass($className);

            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            $controllerHasUsage = false;

            foreach ($methods as $method) {
                if ($method->class !== $className) continue;
                if ($method->isConstructor()) continue;

                $fullName = "$className@{$method->name}";

                if (!in_array($fullName, $usedMethods)) {
                    $unusedMethods[] = $fullName;
                } else {
                    $controllerHasUsage = true;
                }
            }

            if (!$controllerHasUsage) {
                $unusedControllers[] = $className;
            }
        }

        $this->info("📌 Unused Controllers:");
        foreach ($unusedControllers as $ctrl) {
            $this->warn("  - $ctrl");
        }

        $this->info("\n📌 Unused Methods:");
        foreach ($unusedMethods as $method) {
            $this->error("  - $method");
        }

        $allRouteActions = array_unique($usedMethods);
        $this->info("\n📌 Total Used Routes: " . count($allRouteActions));

        $this->info("\n=== ✅ Done ===\n");

        return 0;
    }
}
