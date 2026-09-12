<?php

declare(strict_types=1);

namespace Rehla\Documents\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Rehla\Documents\Enums\DocumentPurpose;

class UploadSession extends Model
{
    use HasUuids;

    protected $table = 'upload_sessions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'owner_id',
        'purpose',
        'expires_at',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => DocumentPurpose::class,
            'expires_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'upload_session_id', 'id');
    }
}
