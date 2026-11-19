<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    use LogsActivity;

    protected $fillable = [
        'key',
        'value',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('master')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $record = static::query()->where('key', $key)->first();

        return $record !== null ? ($record->value ?? $default) : $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    /**
     * @return array{hospital_name: string, hospital_address: string, hospital_phone: string, hospital_email: string}
     */
    public static function hospitalProfile(): array
    {
        $values = static::query()
            ->whereIn('key', ['hospital_name', 'hospital_address', 'hospital_phone', 'hospital_email'])
            ->pluck('value', 'key');

        return [
            'hospital_name' => (string) ($values->get('hospital_name') ?? ''),
            'hospital_address' => (string) ($values->get('hospital_address') ?? ''),
            'hospital_phone' => (string) ($values->get('hospital_phone') ?? ''),
            'hospital_email' => (string) ($values->get('hospital_email') ?? ''),
        ];
    }
}
