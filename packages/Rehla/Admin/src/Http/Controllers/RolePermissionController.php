<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Rehla\Identity\Queries\ListRolesAndAbilities;

final class RolePermissionController extends Controller
{
    public function index(ListRolesAndAbilities $listRolesAndAbilities): View
    {
        $roles = $listRolesAndAbilities->execute();

        return view('rehla-admin::roles.index', [
            'roles' => $roles,
        ]);
    }
}
