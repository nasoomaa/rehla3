<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Rehla\Catalog\Queries\ListAllServices;
use Rehla\Forms\Actions\CreateFormDraft;
use Rehla\Forms\Actions\PublishFormVersion;
use Rehla\Forms\Actions\UpdateFormDraft;
use Rehla\Forms\Data\FormFieldData;
use Rehla\Forms\Queries\ListFormVersions;

final class FormController extends Controller
{
    public function index(ListFormVersions $listFormVersions, ListAllServices $listAllServices): View
    {
        $forms = $listFormVersions->execute();
        $services = $listAllServices->execute();

        return view('rehla-admin::forms.index', [
            'forms' => $forms,
            'services' => $services,
        ]);
    }

    public function storeVersion(string $serviceId, Request $request, CreateFormDraft $createFormDraft): RedirectResponse
    {
        $rawSchema = $request->input('schema') ?? $request->input('schema_json');
        $schemaData = is_string($rawSchema) ? json_decode($rawSchema, true) : $rawSchema;
        $fieldsRaw = $schemaData['fields'] ?? [];

        $fields = array_map(
            fn (array $f): FormFieldData => FormFieldData::fromArray($f),
            $fieldsRaw
        );

        $actorId = (string) Auth::guard('admin')->id();
        $createFormDraft->execute($serviceId, $fields, actorId: $actorId);

        return redirect('/admin/application-forms')->with('success', 'Form draft created successfully.');
    }

    public function updateDraft(string $id, Request $request, UpdateFormDraft $updateFormDraft): RedirectResponse
    {
        $rawSchema = $request->input('schema') ?? $request->input('schema_json');
        $schemaData = is_string($rawSchema) ? json_decode($rawSchema, true) : $rawSchema;
        $fieldsRaw = $schemaData['fields'] ?? [];

        $fields = array_map(
            fn (array $f): FormFieldData => FormFieldData::fromArray($f),
            $fieldsRaw
        );

        $actorId = (string) Auth::guard('admin')->id();
        $updateFormDraft->execute($id, $fields, actorId: $actorId);

        return redirect('/admin/application-forms')->with('success', 'Form draft schema updated successfully.');
    }

    public function publishVersion(string $id, PublishFormVersion $publishFormVersion): RedirectResponse
    {
        $actorId = (string) Auth::guard('admin')->id();
        $publishFormVersion->execute($id, actorId: $actorId);

        return redirect('/admin/application-forms')->with('success', 'Form version published successfully.');
    }
}
