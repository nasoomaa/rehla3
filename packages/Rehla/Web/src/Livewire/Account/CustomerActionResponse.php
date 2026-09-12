<?php

declare(strict_types=1);

namespace Rehla\Web\Livewire\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Rehla\Documents\Actions\BeginUpload;
use Rehla\Documents\Actions\ScanDocument;
use Rehla\Documents\Actions\StoreUpload;
use Rehla\Documents\Data\BeginUploadData;
use Rehla\Documents\Enums\DocumentPurpose;
use Rehla\Fulfillment\Actions\RespondToCustomerAction;
use Rehla\Fulfillment\Data\RespondToCustomerActionData;
use Rehla\Fulfillment\Queries\GetOwnedCustomerActionRequest;

final class CustomerActionResponse extends Component
{
    use WithFileUploads;

    public string $actionRequestId = '';

    public string $orderId = '';

    public string $descriptionEn = '';

    public string $descriptionAr = '';

    public ?string $requiredDocumentPurpose = null;

    public ?string $dueAt = null;

    public ?string $message = null;

    public mixed $document = null;

    public function mount(string $actionRequestId): void
    {
        $this->actionRequestId = $actionRequestId;
        $accountId = (string) Auth::id();

        try {
            $result = app(GetOwnedCustomerActionRequest::class)->handle($accountId, $this->actionRequestId);
            $this->descriptionEn = $result['request']->descriptionEn;
            $this->descriptionAr = $result['request']->descriptionAr;
            $this->requiredDocumentPurpose = $result['request']->requiredDocumentPurpose;
            $this->dueAt = $result['request']->dueAt?->format('Y-m-d H:i');
            $this->orderId = $result['orderId'];
        } catch (\Throwable $e) {
            abort(403);
        }
    }

    public function respond(): mixed
    {
        $accountId = (string) Auth::id();

        if ($this->requiredDocumentPurpose !== null) {
            $this->validate([
                'document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
                'message' => ['nullable', 'string', 'max:1000'],
            ]);
        } else {
            $this->validate([
                'message' => ['required', 'string', 'max:1000'],
            ]);
        }

        $documentId = null;
        if ($this->document !== null) {
            $purpose = DocumentPurpose::tryFrom($this->requiredDocumentPurpose ?? '') ?? DocumentPurpose::Other;

            $session = app(BeginUpload::class)->handle(new BeginUploadData(
                ownerId: $accountId,
                purpose: $purpose,
            ));

            $stored = app(StoreUpload::class)->handle($session->id, $this->document);
            $scanned = app(ScanDocument::class)->handle($stored->id);
            $documentId = $scanned->id;
        }

        app(RespondToCustomerAction::class)->execute(new RespondToCustomerActionData(
            actionRequestId: $this->actionRequestId,
            accountId: $accountId,
            message: $this->message,
            documentId: $documentId,
        ));

        session()->flash('success', __('Your response has been submitted successfully.'));

        return $this->redirect('/account/orders/'.$this->orderId, navigate: true);
    }

    public function render(): View
    {
        return view('rehla-web::livewire.account.customer-action-response')->layout('rehla-web::layouts.app', [
            'title' => __('Respond to Action Request'),
        ]);
    }
}
