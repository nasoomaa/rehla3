<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Rehla\Audit\Queries\ListAuditEntries;

final class AuditLogController extends Controller
{
    public function index(ListAuditEntries $listAuditEntries): View
    {
        $entries = $listAuditEntries->execute();

        return view('rehla-admin::audit.index', [
            'entries' => $entries,
        ]);
    }
}
