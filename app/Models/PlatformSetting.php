<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'description'];

    /**
     * Get a setting value
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("platform_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            return self::parseValue($setting);
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, string $type = 'string'): void
    {
        $setting = self::firstOrCreate(
            ['key' => $key],
            ['type' => $type]
        );

        $setting->value = self::formatValue($value, $type);
        $setting->type = $type;
        $setting->save();

        Cache::forget("platform_setting_{$key}");
    }

    /**
     * Parse value based on type
     */
    protected static function parseValue($setting)
    {
        if ($setting->type === 'encrypted') {
            return $setting->value ? Crypt::decryptString($setting->value) : null;
        }

        if ($setting->type === 'boolean') {
            return (bool) $setting->value;
        }

        if ($setting->type === 'json') {
            return json_decode($setting->value, true);
        }

        return $setting->value;
    }

    /**
     * Format value for storage
     */
    protected static function formatValue($value, string $type)
    {
        if ($type === 'encrypted') {
            return $value ? Crypt::encryptString($value) : null;
        }

        if ($type === 'boolean') {
            return $value ? '1' : '0';
        }

        if ($type === 'json') {
            return json_encode($value);
        }

        return $value;
    }
}

