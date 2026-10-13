{{--
    The pager under a list: previous, numbered pages with the current one
    picked out, next (resources/css/ui/pagination.css). Used as
    $paginator->links('ui.pagination'). Nothing at all for a single page.
--}}
@if ($paginator->hasPages())
    <nav>
        <ul class="pager">
            @if ($paginator->onFirstPage())
                <li class="pager__item" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="pager__link" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="pager__item">
                    <a class="pager__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="pager__item" aria-disabled="true"><span class="pager__link">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="pager__item is-current" aria-current="page"><span class="pager__link">{{ $page }}</span></li>
                        @else
                            <li class="pager__item"><a class="pager__link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="pager__item">
                    <a class="pager__link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="pager__item" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="pager__link" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
