<?php

declare(strict_types=1);

namespace Rehla\Audit\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditEntry extends Model
{
    use HasUuids;

    protected $table = 'audit_entries';

    public $timestamps = false;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'actor_type',
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
        'ip_hash',
        'user_agent_hash',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
