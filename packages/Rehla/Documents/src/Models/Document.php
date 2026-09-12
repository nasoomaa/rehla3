<?php

declare(strict_types=1);

namespace Rehla\Documents\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Rehla\Documents\Data\DocumentRef;
use Rehla\Documents\Enums\DocumentPurpose;
use Rehla\Documents\Enums\DocumentStatus;

class Document extends Model
{
    use HasUuids;

    protected $table = 'documents';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'upload_session_id',
        'owner_id',
        'purpose',
        'disk',
        'storage_key',
        'original_name',
        'detected_mime',
        'size_bytes',
        'sha256',
        'status',
        'rejection_code',
        'scanned_at',
        'attached_at',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => DocumentPurpose::class,
            'status' => DocumentStatus::class,
            'size_bytes' => 'integer',
            'scanned_at' => 'datetime',
            'attached_at' => 'datetime',
        ];
    }

    public function uploadSession(): BelongsTo
    {
        return $this->belongsTo(UploadSession::class, 'upload_session_id', 'id');
    }

    public function toRef(): DocumentRef
    {
        return new DocumentRef(
            id: (string) $this->id,
            ownerId: (string) $this->owner_id,
            purpose: $this->purpose,
            status: $this->status,
            originalName: (string) $this->original_name,
            sizeBytes: (int) $this->size_bytes,
            sha256: (string) $this->sha256,
        );
    }
}
