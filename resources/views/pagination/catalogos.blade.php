@if ($paginator->hasPages())
    <nav class="catalog-pagination-nav" role="navigation" aria-label="Paginacion">
        <p>
            Mostrando
            <strong>{{ $paginator->firstItem() }}</strong>
            a
            <strong>{{ $paginator->lastItem() }}</strong>
            de
            <strong>{{ $paginator->total() }}</strong>
            registros
        </p>

        <div class="catalog-page-links">
            @if ($paginator->onFirstPage())
                <span class="catalog-page-link is-disabled" aria-disabled="true" aria-label="Pagina anterior">
                    <iconify-icon icon="lucide:chevron-left" aria-hidden="true"></iconify-icon>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="catalog-page-link" rel="prev" aria-label="Pagina anterior">
                    <iconify-icon icon="lucide:chevron-left" aria-hidden="true"></iconify-icon>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="catalog-page-link is-disabled" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="catalog-page-link is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="catalog-page-link" aria-label="Ir a pagina {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="catalog-page-link" rel="next" aria-label="Pagina siguiente">
                    <iconify-icon icon="lucide:chevron-right" aria-hidden="true"></iconify-icon>
                </a>
            @else
                <span class="catalog-page-link is-disabled" aria-disabled="true" aria-label="Pagina siguiente">
                    <iconify-icon icon="lucide:chevron-right" aria-hidden="true"></iconify-icon>
                </span>
            @endif
        </div>
    </nav>
@endif
