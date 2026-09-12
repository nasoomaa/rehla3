<?php

declare(strict_types=1);

namespace Rehla\Documents\Contracts;

use Rehla\Documents\Data\DocumentRef;
use Rehla\Documents\Enums\DocumentPurpose;

interface OwnedDocuments
{
    /**
     * @param  list<string>  $documentIds
     * @return list<DocumentRef>
     */
    public function assertCleanOwned(array $documentIds, string $ownerId, DocumentPurpose $purpose): array;
}
