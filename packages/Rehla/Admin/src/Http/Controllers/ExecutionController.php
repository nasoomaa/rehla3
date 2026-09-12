<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Rehla\Fulfillment\Actions\AddInternalNote;
use Rehla\Fulfillment\Actions\RequestCustomerAction;
use Rehla\Fulfillment\Actions\TransitionExecution;
use Rehla\Fulfillment\Data\AddInternalNoteData;
use Rehla\Fulfillment\Data\RequestCustomerActionData;
use Rehla\Fulfillment\Data\TransitionExecutionData;
use Rehla\Fulfillment\Enums\ExecutionStatus;
use Rehla\Fulfillment\Queries\GetExecutionForOperations;
use Rehla\Fulfillment\Queries\ListExecutionsForOperations;
use Rehla\Identity\Queries\GetActorForUser;

final class ExecutionController extends Controller
{
    public function index(ListExecutionsForOperations $listExecutions): View
    {
        $executions = $listExecutions->execute();

        return view('rehla-admin::executions.index', [
            'executions' => $executions,
        ]);
    }

    public function show(
        string $id,
        GetExecutionForOperations $getExecution,
        GetActorForUser $getActorForUser
    ): View {
        $actor = $getActorForUser->handle((string) Auth::guard('admin')->id());
        if ($actor === null) {
            abort(403, 'Unauthorized');
        }

        $execution = $getExecution->handle($id, $actor);

        return view('rehla-admin::executions.show', [
            'execution' => $execution,
        ]);
    }

    public function transition(
        string $id,
        Request $request,
        TransitionExecution $transitionExecution,
        GetActorForUser $getActorForUser
    ): RedirectResponse {
        $validated = $request->validate([
            'target_status' => ['required', 'string'],
            'reason' => ['nullable', 'string'],
        ]);

        $actor = $getActorForUser->handle((string) Auth::guard('admin')->id());
        if ($actor === null) {
            abort(403, 'Unauthorized');
        }

        $toStatus = ExecutionStatus::from($validated['target_status']);

        $transitionExecution->execute(new TransitionExecutionData(
            executionId: $id,
            toStatus: $toStatus,
            actor: $actor,
            reason: $validated['reason'] ?? null,
        ));

        return redirect("/admin/service-executions/{$id}")->with('success', 'Execution status transitioned.');
    }

    public function requestCustomerAction(
        string $id,
        Request $request,
        RequestCustomerAction $requestCustomerAction,
        GetActorForUser $getActorForUser
    ): RedirectResponse {
        $validated = $request->validate([
            'description_en' => ['required', 'string'],
            'description_ar' => ['required', 'string'],
            'required_document_purpose' => ['nullable', 'string'],
            'due_in_hours' => ['nullable', 'integer', 'min:1'],
        ]);

        $actor = $getActorForUser->handle((string) Auth::guard('admin')->id());
        if ($actor === null) {
            abort(403, 'Unauthorized');
        }

        $dueAt = isset($validated['due_in_hours'])
            ? (new DateTimeImmutable)->modify("+{$validated['due_in_hours']} hours")
            : null;

        $requestCustomerAction->execute(new RequestCustomerActionData(
            executionId: $id,
            actor: $actor,
            descriptionEn: $validated['description_en'],
            descriptionAr: $validated['description_ar'],
            requiredDocumentPurpose: $validated['required_document_purpose'] ?? null,
            dueAt: $dueAt,
        ));

        return redirect("/admin/service-executions/{$id}")->with('success', 'Customer action requested.');
    }

    public function addNote(
        string $id,
        Request $request,
        AddInternalNote $addInternalNote,
        GetActorForUser $getActorForUser
    ): RedirectResponse {
        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $actor = $getActorForUser->handle((string) Auth::guard('admin')->id());
        if ($actor === null) {
            abort(403, 'Unauthorized');
        }

        $addInternalNote->execute(new AddInternalNoteData(
            executionId: $id,
            actor: $actor,
            body: $validated['body'],
        ));

        return redirect("/admin/service-executions/{$id}")->with('success', 'Internal note added.');
    }
}
