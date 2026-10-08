@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-ink-500">
            Résultats {{ $paginator->firstItem() }} à {{ $paginator->lastItem() }} sur {{ $paginator->total() }}
        </p>
        <ul class="flex items-center gap-1">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="inline-flex min-h-11 items-center rounded border border-ink-200 px-3 text-ink-500" aria-hidden="true">‹ Précédent</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex min-h-11 items-center rounded border border-ink-300 bg-white px-3 hover:bg-ink-100">‹ Précédent<span class="sr-only">e page</span></a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="inline-flex min-h-11 items-center px-2 text-ink-500" aria-hidden="true">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded bg-accent-600 px-3 font-medium text-white"><span class="sr-only">Page </span>{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded border border-ink-300 bg-white px-3 hover:bg-ink-100"><span class="sr-only">Page </span>{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex min-h-11 items-center rounded border border-ink-300 bg-white px-3 hover:bg-ink-100">Suivant<span class="sr-only">e page</span> ›</a>
                @else
                    <span class="inline-flex min-h-11 items-center rounded border border-ink-200 px-3 text-ink-500" aria-hidden="true">Suivant ›</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
