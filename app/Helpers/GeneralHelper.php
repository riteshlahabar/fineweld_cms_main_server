<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (! function_exists('getMorphedModelName')) {
    /**
     * Get the morphed model name for a given class.
     */
    function getMorphedModelName(string $class): string
    {
        return array_search($class, Relation::morphMap()) ?: class_basename($class);
    }
}

if (! function_exists('getAppVersion')) {
    /**
     * Get the current application version.
     *
     * @return string
     */
    function getAppVersion()
    {
        return env('APP_VERSION', '1.0.0'); // Default version if not set in .env
    }
}

if (! function_exists('getAssetVersion')) {
    /**
     * Get the current application version.
     *
     * @return string
     */
    function getAssetVersion()
    {
        return env('ASSET_VERSION', 123456789); // Default version if not set in .env
    }
}

if (! function_exists('getDatabaseMigrationAppVersion')) {
    /**
     * Get the current application version.
     *
     * @return string
     */
    function getDatabaseMigrationAppVersion()
    {
        // Get the application version from the database
        // get the last record of the version table
        $appVersion = DB::table('versions')
            ->select('version')
            ->orderBy('id', 'desc')
            ->first();

        return $appVersion->version;

        return env('APP_VERSION', '1.0.0'); // Default version if not set in .env
    }
}

if (! function_exists('versionedAsset')) {
    /**
     * Get the current application version.
     *
     * @return string
     */
    function versionedAsset($link)
    {
        $version = (string) getAssetVersion();
        $assetPath = public_path(ltrim($link, '/'));

        // Add file modified time so updated JS/CSS is reflected without manual cache bumps.
        if (is_file($assetPath)) {
            $version .= '-'.filemtime($assetPath);
        }

        return global_asset($link).'?v='.$version;
    }
}

if (! function_exists('isPartnershipModuleActive')) {
    /**
     * Generate a URL for an asset using the global asset URL.
     *
     * @param  string  $path
     * @return string
     */
    function isPartnershipModuleActive()
    {
        if (!Schema::hasTable('installed_modules')) {
            return false;
        }

        $module = \App\Models\InstalledModule::where('name', 'Partnership')->first();

        return $module && $module->is_active;
    }
}
