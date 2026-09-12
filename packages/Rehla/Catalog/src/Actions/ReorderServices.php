<?php

declare(strict_types=1);

namespace Rehla\Catalog\Actions;

use Illuminate\Support\Facades\DB;
use Rehla\Catalog\Models\Service;

final class ReorderServices
{
    /**
     * @param  array<string, int>  $orders  [serviceId => sortOrder]
     */
    public function execute(array $orders): void
    {
        DB::transaction(function () use ($orders): void {
            foreach ($orders as $serviceId => $sortOrder) {
                Service::where('id', $serviceId)->update(['sort_order' => $sortOrder]);
            }
        });
    }
}
