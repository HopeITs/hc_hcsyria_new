<?php

use App\Models\RootsOfHope;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * /rootsofhope/{id}: one edition read as a page-turning book, drawn in the
 * browser from the edition's uploaded PDF (see resources/js/flipbook.js). It
 * replaces the heyzine.com flipbook the editions used to link to. Only an
 * online edition that has a PDF can be read; anything else is a 404.
 */
new #[Layout('layouts::reader', ['title' => 'Roots of Hope'])] class extends Component
{
    #[Locked]
    public int $editionId = 0;

    public function mount(int $id): void
    {
        $edition = RootsOfHope::query()->where('online', 1)->find($id);

        abort_if($edition === null || $edition->file_path === '0' || $edition->file_path === '', 404);

        $this->editionId = $edition->getKey();
    }

    public function with(): array
    {
        return ['edition' => RootsOfHope::query()->findOrFail($this->editionId)];
    }
};
?>

<div>
    <div class="flipbook" x-data="flipbook(@js($edition->file_path))" x-on:keydown.left.window="prev()" x-on:keydown.right.window="next()">
        <div class="flipbook__bar">
            <a href="{{ route('roots') }}" wire:navigate class="flipbook__back">&larr; Roots of Hope</a>
            <span class="flipbook__title">{{ $edition->title }}</span>
            <span class="flipbook__tools">
                <a href="{{ $edition->file_path }}" download class="flipbook__btn">Download</a>
                <button type="button" class="flipbook__btn" x-on:click="fullscreen()" aria-label="Full screen">&#x26F6;</button>
            </span>
        </div>

        <div class="flipbook__stage-wrap" wire:ignore>
            <p class="flipbook__note" x-show="state === 'loading'">Opening the magazine&hellip;</p>
            <p class="flipbook__note" x-show="state === 'failed'" x-cloak>
                The magazine cannot be shown here. <a href="{{ $edition->file_path }}" download>Download the PDF</a> instead.
            </p>
            <div class="flipbook__frame" x-ref="frame">
                <div class="flipbook__stage" x-ref="stage" x-show="state === 'ready'" x-cloak></div>
            </div>
        </div>

        <div class="flipbook__nav" x-show="state === 'ready'" x-cloak>
            <button type="button" class="flipbook__btn" x-on:click="first()" aria-label="First page">&laquo;</button>
            <button type="button" class="flipbook__btn" x-on:click="prev()" aria-label="Previous page">&lsaquo;</button>
            <span class="flipbook__count"><span x-text="page"></span> / <span x-text="total"></span></span>
            <button type="button" class="flipbook__btn" x-on:click="next()" aria-label="Next page">&rsaquo;</button>
            <button type="button" class="flipbook__btn" x-on:click="last()" aria-label="Last page">&raquo;</button>
        </div>
    </div>
</div>
