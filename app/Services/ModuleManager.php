<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;
use App\Models\InstalledModule;
use Illuminate\Support\Facades\Log;

class ModuleManager
{
    public function installPartnershipModule($zipFile)
    {
        // Validate it's a Partnership module
        if (!$this->isValidPartnershipModule($zipFile)) {
            throw new \Exception('Invalid Partnership module file');
        }

        $tempPath = storage_path('app/temp/partnership_' . time());

        // Extract module
        $this->extractModule($zipFile, $tempPath);

        // Validate module structure
        $moduleConfig = $this->validateModuleStructure($tempPath);

        // Check if already installed
        if ($this->isModuleInstalled('Partnership')) {
            throw new \Exception('Partnership module is already installed');
        }

        // Install the module
        $this->installModule($tempPath, $moduleConfig);

        // Cleanup
        File::deleteDirectory($tempPath);

        return true;
    }

    protected function isValidPartnershipModule($file)
    {
        return $file->getClientOriginalExtension() === 'zip' &&
               str_contains($file->getClientOriginalName(), 'Partnership');
    }

    protected function extractModule($zipFile, $extractPath)
    {
        File::ensureDirectoryExists($extractPath);

        $zip = new ZipArchive;
        if ($zip->open($zipFile->getRealPath()) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();
            return true;
        }

        throw new \Exception('Failed to extract module file');
    }

    protected function validateModuleStructure($path)
    {
        $moduleConfigPath = $path . '/module.json';

        if (!File::exists($moduleConfigPath)) {
            // Check if it's in a subdirectory
            $subdirs = File::directories($path);
            if (count($subdirs) === 1) {
                $moduleConfigPath = $subdirs[0] . '/module.json';
            }
        }

        if (!File::exists($moduleConfigPath)) {
            throw new \Exception('Invalid module structure: module.json not found');
        }

        $config = json_decode(File::get($moduleConfigPath), true);

        if ($config['name'] !== 'Partnership') {
            throw new \Exception('This is not a Partnership module');
        }

        return $config;
    }

    protected function installModule($tempPath, $config)
    {
        $moduleName = $config['name'];
        $finalPath = base_path("Modules/{$moduleName}");

        // Find the actual module directory
        $moduleDir = $this->findModuleDirectory($tempPath, $moduleName);

        // Remove existing if any (for updates)
        if (File::exists($finalPath)) {
            File::deleteDirectory($finalPath);
        }

        // Move to Modules directory
        File::move($moduleDir, $finalPath);

        // Run module installation
        $this->runModuleInstallation($moduleName, $config);

        // Register module
        InstalledModule::create([
            'name' => $moduleName,
            'version' => $config['version'],
            'is_active' => true,
            'description' => $config['description'] ?? null,
        ]);
    }

    protected function findModuleDirectory($tempPath, $moduleName)
    {
        // Check if module is directly in temp path
        $directPath = $tempPath . '/' . $moduleName;
        if (File::exists($directPath)) {
            return $directPath;
        }

        // Look for module in subdirectories
        $subdirs = File::directories($tempPath);
        foreach ($subdirs as $subdir) {
            if (File::exists($subdir . '/module.json')) {
                $config = json_decode(File::get($subdir . '/module.json'), true);
                if ($config['name'] === $moduleName) {
                    return $subdir;
                }
            }
        }

        throw new \Exception('Could not find module directory');
    }

    protected function runModuleInstallation($moduleName, $config)
    {
        // Run migrations
        Artisan::call('migrate', [
            '--path' => "Modules/{$moduleName}/database/migrations",
            '--force' => true
        ]);

        // Run seeders if any
        if (isset($config['seeders'])) {
            foreach ($config['seeders'] as $seeder) {
                Artisan::call('db:seed', [
                    '--class' => $seeder,
                    '--force' => true
                ]);
            }
        }

        // Clear caches to ensure service provider discovery works
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('package:discover'); // This discovers new service providers
    }

    // protected function runModuleInstallation($moduleName, $config)
    // {
    //     // Run migrations
    //     Artisan::call('migrate', [
    //         '--path' => "Modules/{$moduleName}/database/migrations",
    //         '--force' => true
    //     ]);

    //     // Run seeders if any
    //     if (isset($config['seeders'])) {
    //         foreach ($config['seeders'] as $seeder) {
    //             Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
    //         }
    //     }

    //     // Update composer autoload
    //     $this->updateComposerAutoload($moduleName);

    //     // Clear caches
    //     Artisan::call('config:clear');
    //     Artisan::call('route:clear');
    // }

    protected function updateComposerAutoload($moduleName)
    {
        // Remove this entire method or comment it out
        // Laravel's service provider discovery should handle module autoloading

        Log::info("Skipping composer autoload update for module: {$moduleName}");
        return true;
    }

    // protected function updateComposerAutoload($moduleName)
    // {
    //     $composerPath = base_path('composer.json');
    //     $composer = json_decode(File::get($composerPath), true);

    //     // Add module to autoload
    //     $composer['autoload']['psr-4']["Modules\\{$moduleName}\\"] = "Modules/{$moduleName}/src/";

    //     File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    //     // Dump autoload
    //     shell_exec('cd ' . base_path() . ' && composer dump-autoload 2>&1');
    // }

    protected function isModuleInstalled($moduleName)
    {
        return InstalledModule::where('name', $moduleName)->exists();
    }

    public function getInstalledModules()
    {
        return InstalledModule::all();
    }


    //Uninstallation function
    public function uninstallPartnershipModule()
    {
        $moduleName = 'Partnership';

        // Check if module is installed
        if (!$this->isModuleInstalled($moduleName)) {
            throw new \Exception('Partnership module is not installed.');
        }

        // Get module info before removal
        $moduleInfo = $this->getModuleInfo($moduleName);

        // Rollback migrations
        //$this->rollbackMigrations($moduleName);

        // Remove module directory
        $this->removeModuleDirectory($moduleName);

        // Remove from composer autoload
        $this->removeFromComposerAutoload($moduleName);

        // Remove from installed Modules table
        $this->removeFromDatabase($moduleName);

        // Clear caches
        $this->clearCaches();

        Log::info("Partnership module {$moduleInfo['version']} uninstalled successfully");

        return $moduleInfo;
    }

    /**
     * Rollback module migrations
     */
    protected function rollbackMigrations($moduleName)
    {
        $migrationPath = base_path("Modules/{$moduleName}/database/migrations");

        if (File::exists($migrationPath)) {
            try {
                Artisan::call('migrate:rollback', [
                    '--path' => "Modules/{$moduleName}/database/migrations",
                    '--force' => true
                ]);
            } catch (\Exception $e) {
                Log::warning("Failed to rollback {$moduleName} migrations: " . $e->getMessage());
                // Don't throw exception, continue with uninstall
            }
        }
    }

    /**
     * Remove module directory
     */
    protected function removeModuleDirectory($moduleName)
    {
        $modulePath = base_path("Modules/{$moduleName}");

        if (File::exists($modulePath)) {
            if (!File::deleteDirectory($modulePath)) {
                throw new \Exception("Failed to remove module files from Modules/{$moduleName}. Please check directory permissions.");
            }
        }
    }

    /**
     * Remove module from composer autoload
     */

    protected function removeFromComposerAutoload($moduleName)
    {
        // Remove this method as well
        Log::info("Skipping composer autoload removal for module: {$moduleName}");
        return true;
    }


    // protected function removeFromComposerAutoload($moduleName)
    // {
    //     $composerPath = base_path('composer.json');

    //     if (!File::exists($composerPath)) {
    //         return;
    //     }

    //     $composer = json_decode(File::get($composerPath), true);

    //     if (isset($composer['autoload']['psr-4'])) {
    //         $updated = false;

    //         // Remove module namespace from autoload
    //         foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
    //             if (str_contains($namespace, $moduleName) || str_contains($path, $moduleName)) {
    //                 unset($composer['autoload']['psr-4'][$namespace]);
    //                 $updated = true;
    //             }
    //         }

    //         if ($updated) {
    //             File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    //             // Dump autoload
    //             $output = shell_exec('cd ' . base_path() . ' && composer dump-autoload 2>&1');
    //             Log::info('Composer autoload dumped after uninstall: ' . $output);
    //         }
    //     }
    // }

    /**
     * Remove module from database
     */
    protected function removeFromDatabase($moduleName)
    {
        InstalledModule::where('name', $moduleName)->delete();
    }

    /**
     * Clear application caches
     */
    protected function clearCaches()
    {
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
    }

    /**
     * Get module information
     */
    protected function getModuleInfo($moduleName)
    {
        $configPath = base_path("Modules/{$moduleName}/module.json");

        if (File::exists($configPath)) {
            return json_decode(File::get($configPath), true);
        }

        // Fallback to database info
        $module = InstalledModule::where('name', $moduleName)->first();
        if ($module) {
            return [
                'name' => $module->name,
                'version' => $module->version
            ];
        }

        return ['name' => $moduleName, 'version' => 'unknown'];
    }

    /**
     * Check if module files exist (not just in database)
     */
    public function isModuleFilesExist($moduleName)
    {
        return File::exists(base_path("Modules/{$moduleName}"));
    }

    /**
     * Get detailed module status
     */
    public function getModuleStatus($moduleName)
    {
        $isInstalled = $this->isModuleInstalled($moduleName);
        $filesExist = $this->isModuleFilesExist($moduleName);
        $moduleInfo = $isInstalled ? $this->getModuleInfo($moduleName) : null;

        return [
            'name' => $moduleName,
            'is_installed' => $isInstalled,
            'files_exist' => $filesExist,
            'info' => $moduleInfo,
            'status' => $isInstalled && $filesExist ? 'active' : ($isInstalled ? 'database_only' : 'not_installed')
        ];
    }

    //Activate module
    public function activatePartnershipModule()
    {
        $module = InstalledModule::where('name', 'Partnership')->first();
        if ($module) {
            $module->is_active = true;
            $module->save();
            return true;
        }
        throw new \Exception('Partnership module is not installed.');
    }

    //Deactivate module
    public function deactivatePartnershipModule()
    {
        $module = InstalledModule::where('name', 'Partnership')->first();
        if ($module) {
            $module->is_active = false;
            $module->save();
            return true;
        }
        throw new \Exception('Partnership module is not installed.');
    }

}
