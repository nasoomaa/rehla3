<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Rehla\Identity\Queries\GetActorForUser;
use Rehla\TopUps\Actions\ApproveTopUp;
use Rehla\TopUps\Actions\RejectTopUp;
use Rehla\TopUps\Data\ApproveTopUpData;
use Rehla\TopUps\Data\RejectTopUpData;
use Rehla\TopUps\Queries\ListAllTopUps;

final class TopUpController extends Controller
{
    public function index(ListAllTopUps $listAllTopUps): View
    {
        $topUps = $listAllTopUps->execute();

        return view('rehla-admin::top-ups.index', [
            'topUps' => $topUps,
        ]);
    }

    public function approve(
        string $id,
        Request $request,
        ApproveTopUp $approveTopUp,
        GetActorForUser $getActorForUser
    ): RedirectResponse {
        $actor = $getActorForUser->handle((string) Auth::guard('admin')->id());
        if ($actor === null) {
            abort(403, 'Unauthorized');
        }

        $approveTopUp->execute(new ApproveTopUpData(
            topUpId: $id,
            actor: $actor,
        ));

        return redirect('/admin/top-up-requests')->with('success', 'Top-up approved successfully.');
    }

    public function reject(
        string $id,
        Request $request,
        RejectTopUp $rejectTopUp,
        GetActorForUser $getActorForUser
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $actor = $getActorForUser->handle((string) Auth::guard('admin')->id());
        if ($actor === null) {
            abort(403, 'Unauthorized');
        }

        $rejectTopUp->execute(new RejectTopUpData(
            topUpId: $id,
            actor: $actor,
            rejectionReason: $validated['reason'],
        ));

        return redirect('/admin/top-up-requests')->with('success', 'Top-up rejected successfully.');
    }
}
