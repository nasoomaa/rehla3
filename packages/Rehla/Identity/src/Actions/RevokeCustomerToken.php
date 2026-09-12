<?php

declare(strict_types=1);

namespace Rehla\Identity\Actions;

use Illuminate\Support\Facades\DB;

final class RevokeCustomerToken
{
    public function handle(string $tokenId): void
    {
        DB::table('personal_access_tokens')->where('id', $tokenId)->delete();
    }
}
