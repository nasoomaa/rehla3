<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;

it('cannot update or delete an audit entry even with direct SQL', function (): void {
    $id = app(AuditWriter::class)->append(new AppendAuditData(
        actorType: 'staff',
        actorId: (string) Str::uuid(),
        action: 'top_up.approved',
        subjectType: 'top_up',
        subjectId: (string) Str::uuid(),
        metadata: ['amount_minor' => 500000],
    ));

    expect($id)->toBeString()->not->toBeEmpty();

    // Verify row was inserted
    $row = DB::table('audit_entries')->where('id', $id)->first();
    expect($row)->not->toBeNull()
        ->and($row->action)->toBe('top_up.approved');

    // Attempting direct SQL UPDATE must be rejected by PostgreSQL trigger
    expect(fn () => DB::table('audit_entries')->where('id', $id)->update(['action' => 'changed']))
        ->toThrow(QueryException::class);

    // Attempting direct SQL DELETE must be rejected by PostgreSQL trigger
    expect(fn () => DB::table('audit_entries')->where('id', $id)->delete())
        ->toThrow(QueryException::class);
});

it('sanitizes secrets from audit metadata and ignores password or token fields', function (): void {
    $id = app(AuditWriter::class)->append(new AppendAuditData(
        actorType: 'staff',
        actorId: (string) Str::uuid(),
        action: 'customer.password_reset',
        subjectType: 'user',
        subjectId: (string) Str::uuid(),
        metadata: [
            'reason' => 'user_request',
            'password' => 'super_secret',
            'token' => 'bearer_12345',
        ],
    ));

    $row = DB::table('audit_entries')->where('id', $id)->first();
    $metadata = json_decode((string) $row->metadata, true);

    expect($metadata)->toHaveKey('reason')
        ->and($metadata)->not->toHaveKey('password')
        ->and($metadata)->not->toHaveKey('token');
});
