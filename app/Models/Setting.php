<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function companyProfile(): array
    {
        return [
            'name' => static::get('company_name', 'iHome') ?: 'iHome',
            'phone' => static::get('company_phone', ''),
            'email' => static::get('company_email', ''),
            'address' => static::get('company_address', ''),
        ];
    }
}
