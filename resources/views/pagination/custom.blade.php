@if ($paginator->hasPages())
<nav aria-label="Pagination">
    <ul class="pagination justify-content-center mb-0">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i></span></li>
        @else
            <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}"><i class="bi bi-chevron-left"></i></a></li>
        @endif

        {{-- Pages --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}"><i class="bi bi-chevron-right"></i></a></li>
        @else
            <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></li>
        @endif
    </ul>
    <div class="text-center mt-2" style="font-size:12px; color:var(--text-muted);">
        Menampilkan {{ $paginator->firstItem() ?? 0 }} - {{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} data
    </div>
</nav>
@endif
