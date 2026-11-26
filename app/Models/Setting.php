<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string|null $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    /**
     * Retrieve a single setting value by key with an optional default.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $record = static::query()->where('key', $key)->first();

        return $record !== null ? $record->value : $default;
    }

    /**
     * Store or update a single setting value under the given group.
     */
    public static function setValue(string $group, string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['group' => $group, 'value' => $value],
        );
    }

    /**
     * Retrieve all key-value pairs belonging to the specified setting group.
     *
     * @return array<string, string|null>
     */
    public static function getGroup(string $group): array
    {
        /** @var array<string, string|null> $values */
        $values = static::query()
            ->where('group', $group)
            ->pluck('value', 'key')
            ->all();

        return $values;
    }
}
