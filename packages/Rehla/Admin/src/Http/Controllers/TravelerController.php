<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Queries\ListAllTravelers;

final class TravelerController extends Controller
{
    public function index(ListAllTravelers $listAllTravelers): View
    {
        $travelers = $listAllTravelers->execute();

        $maskedTravelers = array_map(function (TravelerData $t): array {
            $passport = $t->passportNumber;
            $len = strlen($passport);
            $maskedPassport = $len > 4
                ? substr($passport, 0, 1).'***'.substr($passport, -3)
                : '***';

            return [
                'id' => $t->id,
                'fullName' => $t->fullName,
                'dateOfBirth' => $t->dateOfBirth,
                'gender' => $t->gender->value,
                'maskedPassport' => $maskedPassport,
                'passportExpiresAt' => $t->passportExpiresAt,
            ];
        }, $travelers);

        return view('rehla-admin::travelers.index', [
            'travelers' => $maskedTravelers,
        ]);
    }
}
