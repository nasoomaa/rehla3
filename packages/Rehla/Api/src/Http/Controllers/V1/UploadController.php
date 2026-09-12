<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rehla\Documents\Actions\BeginUpload;
use Rehla\Documents\Actions\ScanDocument;
use Rehla\Documents\Actions\StoreUpload;
use Rehla\Documents\Data\BeginUploadData;
use Rehla\Documents\Enums\DocumentPurpose;

final class UploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'purpose' => ['required', 'string'],
        ]);

        $accountId = $request->user()->id;
        $purpose = DocumentPurpose::tryFrom($validated['purpose']) ?? DocumentPurpose::BankReceipt;

        $session = app(BeginUpload::class)->handle(new BeginUploadData(
            ownerId: $accountId,
            purpose: $purpose,
        ));

        $doc = app(StoreUpload::class)->handle($session->id, $request->file('file'));
        $cleanDoc = app(ScanDocument::class)->handle($doc->id);

        return response()->json([
            'data' => [
                'document_id' => $cleanDoc->id,
                'status' => $cleanDoc->status->value,
            ],
        ], 201);
    }
}
