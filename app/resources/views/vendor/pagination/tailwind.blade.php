@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-ink-700">
            Résultats {{ $paginator->firstItem() }} à {{ $paginator->lastItem() }} sur {{ $paginator->total() }}
        </p>
        <ul class="flex items-center gap-1.5">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="btn border-2 border-ink-100 bg-ink-100 text-ink-700" aria-hidden="true">‹ Précédent</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary">‹ Précédent<span class="sr-only">e page</span></a>
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
                                <span aria-current="page" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-full bg-ink-900 px-3 font-bold text-white"><span class="sr-only">Page </span>{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-full border-2 border-ink-200 bg-white px-3 font-bold text-ink-900 no-underline hover:border-ink-900"><span class="sr-only">Page </span>{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary">Suivant<span class="sr-only">e page</span> ›</a>
                @else
                    <span class="btn border-2 border-ink-100 bg-ink-100 text-ink-700" aria-hidden="true">Suivant ›</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
