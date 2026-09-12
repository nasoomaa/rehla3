<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Rehla\Content\Actions\CreateContentBlock;
use Rehla\Content\Data\CreateContentBlockData;
use Rehla\Content\Queries\ListContentBlocks;

final class ContentController extends Controller
{
    public function index(ListContentBlocks $listContentBlocks): View
    {
        $blocks = $listContentBlocks->execute();

        return view('rehla-admin::content.index', [
            'blocks' => $blocks,
        ]);
    }

    public function store(Request $request, CreateContentBlock $createContentBlock): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'body_en' => ['nullable', 'string'],
            'body_ar' => ['nullable', 'string'],
        ]);

        $actorId = (string) Auth::guard('admin')->id();

        $createContentBlock->execute(new CreateContentBlockData(
            key: $validated['key'],
            titleEn: $validated['title_en'],
            titleAr: $validated['title_ar'],
            bodyEn: $validated['body_en'] ?? '',
            bodyAr: $validated['body_ar'] ?? '',
        ), actorId: $actorId);

        return redirect('/admin/content')->with('success', 'Content block created successfully.');
    }
}
