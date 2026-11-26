<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property array<int, string>|null $roles
 * @property string|null $ip_address
 * @property string $method
 * @property string $path
 * @property string|null $route_name
 * @property int|null $status_code
 * @property int|null $duration_ms
 * @property string|null $browser
 * @property string|null $platform
 * @property string|null $user_agent
 * @property array<string, mixed>|null $request_payload
 * @property string|null $referer
 * @property Carbon|null $created_at
 */
class RequestLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'roles',
        'ip_address',
        'method',
        'path',
        'route_name',
        'status_code',
        'duration_ms',
        'browser',
        'platform',
        'user_agent',
        'request_payload',
        'referer',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'request_payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the user associated with the request log.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
