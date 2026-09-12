<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Rehla\Identity\Actions\AssignRole;
use Rehla\Identity\Actions\RevokeRole;
use Rehla\Identity\Queries\ListRolesAndAbilities;
use Rehla\Identity\Queries\ListStaffUsers;

final class RolePermissionController extends Controller
{
    public function index(ListRolesAndAbilities $listRolesAndAbilities, ListStaffUsers $listStaffUsers): View
    {
        $roles = $listRolesAndAbilities->execute();
        $staffUsers = $listStaffUsers->execute();

        return view('rehla-admin::roles.index', [
            'roles' => $roles,
            'staffUsers' => $staffUsers,
        ]);
    }

    public function assign(Request $request, AssignRole $assignRole): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'string', 'uuid'],
            'role' => ['required', 'string'],
        ]);

        $assignRole->handle($validated['user_id'], $validated['role']);

        return redirect('/admin/roles-permissions')->with('success', 'Staff role assigned successfully.');
    }

    public function revoke(Request $request, RevokeRole $revokeRole): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'string', 'uuid'],
            'role' => ['required', 'string'],
        ]);

        $revokeRole->handle($validated['user_id'], $validated['role']);

        return redirect('/admin/roles-permissions')->with('success', 'Staff role revoked successfully.');
    }
}
