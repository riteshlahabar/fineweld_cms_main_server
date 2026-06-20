<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CacheService
{
    public static function get($type)
    {
        if (env('INSTALLATION_STATUS')) {
            switch ($type) {
                case 'tax':
                    return self::rememberAll('tax', \App\Models\Tax::class);
                case 'unit':
                    return self::rememberAll('unit', \App\Models\Unit::class);
                case 'appSetting':
                    return self::rememberFirst('appSetting', \App\Models\AppSettings::class);
                case 'company':
                    return self::rememberFirst('company', \App\Models\Company::class);
                case 'warehouse':
                    return self::rememberAll('warehouse', \App\Models\Warehouse::class);
                case 'smtpSettings':
                    return self::rememberFirst('smtpSettings', \App\Models\SmtpSettings::class, (object) [
                        'host' => null,
                        'port' => null,
                        'username' => null,
                        'password' => null,
                        'encryption' => null,
                    ]);
                default:
                    throw new \Exception("Invalid cache type: $type");
            }
        }
    }

    protected static function rememberAll(string $cacheKey, string $modelClass)
    {
        if (! self::hasTable($modelClass)) {
            return collect();
        }

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($modelClass) {
            return $modelClass::all();
        });
    }

    protected static function rememberFirst(string $cacheKey, string $modelClass, mixed $default = null)
    {
        if (! self::hasTable($modelClass)) {
            return $default;
        }

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($modelClass, $default) {
            return $modelClass::first() ?? $default;
        });
    }

    protected static function hasTable(string $modelClass): bool
    {
        return Schema::hasTable((new $modelClass)->getTable());
    }
}
