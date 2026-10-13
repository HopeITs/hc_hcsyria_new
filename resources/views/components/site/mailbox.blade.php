@props(['photos'])

{{--
    School to School's "Students Mailbox": photographs of the children's
    letters. Each sits in a full-height band that sticks as the page scrolls,
    so the next letter slides up over the last and settles at its own slant
    (resources/css/ui/mailbox.css). $photos are image paths.
--}}
<div id="message-section" class="mailbox section-gap">
    <div class="px-3">
        <x-ui.reveal :ms="800" class="-mx-3 flex">
            <div class="mt-4 flex w-full flex-col items-center justify-center px-3">
                <x-ui.section-title>Students Mailbox</x-ui.section-title>
            </div>
        </x-ui.reveal>
    </div>

    <x-ui.red-rule short :ms="600"/>

    <x-ui.reveal :ms="800" class="mailbox__letters px-3">
        <div class="-mx-3">
            <div class="px-3">
                @foreach ($photos as $photo)
                    <div class="mailbox__letter">
                        <img src="{{ $photo }}" alt="">
                    </div>
                @endforeach

                <x-ui.red-rule :ms="800"/>
            </div>
        </div>
    </x-ui.reveal>
</div>
