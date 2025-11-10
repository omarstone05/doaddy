<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Encryption\DecryptException;

class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description', 'is_public'];

    protected $casts = [
        'is_public' => 'boolean',
    ];

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
            if (!$setting->value) {
                return null;
            }
            
            try {
                return Crypt::decryptString($setting->value);
            } catch (DecryptException $e) {
                // If decryption fails, the value might be corrupted or encrypted with a different key
                // Return null so the user can re-enter the value
                \Log::warning("Failed to decrypt setting {$setting->key}: " . $e->getMessage());
                return null;
            }
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

    /**
     * Get setting value with proper type casting
     */
    public function getValue()
    {
        if ($this->value === null) {
            return null;
        }

        return match($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'json' => json_decode($this->value, true),
            'encrypted' => $this->getDecryptedValue(),
            default => $this->value,
        };
    }

    /**
     * Get decrypted value with error handling
     */
    protected function getDecryptedValue()
    {
        if (!$this->value) {
            return null;
        }

        try {
            return Crypt::decryptString($this->value);
        } catch (DecryptException $e) {
            // If decryption fails, the value might be corrupted or encrypted with a different key
            // Return null so the user can re-enter the value
            \Log::warning("Failed to decrypt setting {$this->key}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Set setting value with proper type handling
     */
    public function setValue($value): void
    {
        $this->value = match($this->type) {
            'boolean' => $value ? 'true' : 'false',
            'json' => json_encode($value),
            'encrypted' => $value ? Crypt::encryptString($value) : null,
            default => $value,
        };
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn($setting) => [$setting->key => $setting->getValue()])
            ->toArray();
    }
}

