<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

// إعداد واحد بمفتاح نصي وقيمة JSON (شوف migration create_settings_table). get() بتقرا مرة واحدة
// في الطلب، وبترجع الافتراضي لو الجدول لسه مش موجود (قبل migrate) بدل ما توقّع الصفحة.
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** @var array<string, mixed>|null */
    private static ?array $loaded = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all_();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function put(string $key, mixed $value): void
    {
        if ($value === null) {
            static::query()->whereKey($key)->delete();
        } else {
            static::query()->updateOrCreate(['key' => $key], ['value' => json_encode($value, JSON_UNESCAPED_UNICODE)]);
        }

        self::$loaded = null;
    }

    public static function forget(string $key): void
    {
        self::put($key, null);
    }

    /**
     * @return array<string, mixed>
     */
    private static function all_(): array
    {
        if (self::$loaded !== null) {
            return self::$loaded;
        }

        try {
            if (! Schema::hasTable('settings')) {
                return self::$loaded = [];
            }

            return self::$loaded = static::query()->pluck('value', 'key')
                ->map(fn ($value) => json_decode((string) $value, true))
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    // التستات بتعيد بناء الداتابيز بين كل تست والتاني، فالكاش لازم يتمسح معاها.
    public static function flushCache(): void
    {
        self::$loaded = null;
    }
}
