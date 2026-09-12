<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use JsonException;
use Rehla\Catalog\Queries\ListAllServices;
use Rehla\Forms\Actions\CreateFormDraft;
use Rehla\Forms\Actions\PublishFormVersion;
use Rehla\Forms\Actions\UpdateFormDraft;
use Rehla\Forms\Data\FormFieldData;
use Rehla\Forms\Enums\FieldType;
use Rehla\Forms\Queries\ListFormVersions;
use ValueError;

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
        $fields = $this->parseAndValidateFields($request);

        $actorId = (string) Auth::guard('admin')->id();
        $createFormDraft->execute($serviceId, $fields, actorId: $actorId);

        return redirect('/admin/application-forms')->with('success', 'Form draft created successfully.');
    }

    public function updateDraft(string $id, Request $request, UpdateFormDraft $updateFormDraft): RedirectResponse
    {
        $fields = $this->parseAndValidateFields($request);

        $actorId = (string) Auth::guard('admin')->id();
        $updateFormDraft->execute($id, $fields, actorId: $actorId);

        return redirect('/admin/application-forms')->with('success', 'Form draft schema updated successfully.');
    }

    /**
     * @return array<int, FormFieldData>
     */
    private function parseAndValidateFields(Request $request): array
    {
        $rawSchema = $request->input('schema') ?? $request->input('schema_json');
        if ($rawSchema === null || (is_string($rawSchema) && trim($rawSchema) === '')) {
            abort(422, 'Form schema is required.');
        }

        if (is_string($rawSchema)) {
            try {
                $schemaData = json_decode($rawSchema, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                abort(422, 'Invalid JSON schema format: '.$e->getMessage());
            }
        } else {
            $schemaData = $rawSchema;
        }

        if (! is_array($schemaData) || ! isset($schemaData['fields']) || ! is_array($schemaData['fields']) || empty($schemaData['fields'])) {
            abort(422, 'Schema must contain a non-empty fields array.');
        }

        $fields = [];
        foreach ($schemaData['fields'] as $index => $fieldRaw) {
            if (! is_array($fieldRaw)) {
                abort(422, "Field at index {$index} must be an object/array.");
            }

            $key = $fieldRaw['key'] ?? null;
            if (! is_string($key) || trim($key) === '') {
                abort(422, "Field at index {$index} requires a non-empty key.");
            }

            $labelEn = $fieldRaw['label']['en'] ?? $fieldRaw['label_en'] ?? null;
            $labelAr = $fieldRaw['label']['ar'] ?? $fieldRaw['label_ar'] ?? null;
            if (! is_string($labelEn) || trim($labelEn) === '' || ! is_string($labelAr) || trim($labelAr) === '') {
                abort(422, "Field '{$key}' requires non-empty English and Arabic labels.");
            }

            $type = $fieldRaw['type'] ?? 'short_text';
            try {
                FieldType::from((string) $type);
            } catch (ValueError) {
                abort(422, "Field '{$key}' has an invalid type: {$type}.");
            }

            $fields[] = FormFieldData::fromArray($fieldRaw);
        }

        return $fields;
    }

    public function publishVersion(string $id, PublishFormVersion $publishFormVersion): RedirectResponse
    {
        $actorId = (string) Auth::guard('admin')->id();
        $publishFormVersion->execute($id, actorId: $actorId);

        return redirect('/admin/application-forms')->with('success', 'Form version published successfully.');
    }
}
