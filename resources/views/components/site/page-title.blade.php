@props(['title' => null, 'subtitle' => null, 'compact' => false, 'solo' => false, 'html' => false])

{{--
    The hero title under the navbar: a red corner with two lines of capitals
    (resources/css/ui/page-title.css). The right half of the row is empty
    unless the page puts something in the slot (a programme's logo), and
    otherwise only fixes the frame at half the width, as in the legacy markup.
    :compact is the smaller type the programme pages use; :solo is for the
    pages with no first line, whose one line (HTML allowed) is larger. :html
    lets the subtitle carry HTML without the larger type. A page
    with neither (the Christian Youth page, whose heading sits below the
    cover) gets the red corner alone.
--}}
<div class="page-margin">
    <div class="px-3">
        <div class="-mx-3 flex">
            <div @class(['page-title', 'page-title--compact' => $compact, 'page-title--solo' => $solo])>
                <div class="page-title__frame"></div>
                @if ($title || $subtitle)
                    <div class="page-title__text">
                        @if ($title)
                            <x-ui.reveal :ms="600" class="page-title__line">{{ $title }}</x-ui.reveal>
                        @endif
                        <x-ui.reveal :ms="1000" class="page-title__line page-title__line--strong">{!! $solo || $html ? $subtitle : e($subtitle) !!}</x-ui.reveal>
                    </div>
                @endif
            </div>
            @if ($slot->isEmpty())
                <div class="flex-1 pl-3"></div>
            @else
                <div class="page-title__aside">{{ $slot }}</div>
            @endif
        </div>
    </div>
</div>
