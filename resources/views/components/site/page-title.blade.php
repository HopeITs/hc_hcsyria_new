@props(['title', 'subtitle'])

{{--
    The hero title under the navbar: a red corner with two lines of capitals
    (resources/css/ui/page-title.css). The right half of the row is empty, as
    in the legacy markup, and only fixes the frame at half the width.
--}}
<div class="page-margin">
    <div class="px-3">
        <div class="-mx-3 flex">
            <div class="page-title">
                <div class="page-title__frame"></div>
                <div class="page-title__text">
                    <x-ui.reveal :ms="600" class="page-title__line">{{ $title }}</x-ui.reveal>
                    <x-ui.reveal :ms="1000" class="page-title__line page-title__line--strong">{{ $subtitle }}</x-ui.reveal>
                </div>
            </div>
            <div class="flex-1 pl-3"></div>
        </div>
    </div>
</div>
