@if ($paginator->hasPages())
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="text-sm text-gray-600" id="entriesInfo">
            {{ $entries_info ?? "Showing {$paginator->firstItem()} to {$paginator->lastItem()} of {$paginator->total()} entries" }}
        </div>

        <nav role="navigation" aria-label="Pagination Navigation">
            <ul class="flex items-center gap-1">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li aria-disabled="true">
                        <span class="px-3 py-1 rounded-md text-gray-400 cursor-not-allowed">Previous</span>
                    </li>
                @else
                    <li>
                        <a href="#" data-page="{{ $paginator->currentPage() - 1 }}" 
                           class="px-3 py-1 rounded-md text-gray-700 hover:bg-gray-100 product-pagination-link">Previous</a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @php
                    // Batasi jumlah halaman yang ditampilkan
                    $window = 2; // Tampilkan 2 halaman sebelum dan sesudah halaman aktif
                    $start = max(1, $paginator->currentPage() - $window);
                    $end = min($paginator->lastPage(), $paginator->currentPage() + $window);
                @endphp

                {{-- First Page Link --}}
                @if ($start > 1)
                    <li>
                        <a href="#" data-page="1" class="px-3 py-1 rounded-md text-gray-700 hover:bg-gray-100 product-pagination-link">1</a>
                    </li>
                    @if ($start > 2)
                        <li aria-disabled="true">
                            <span class="px-3 py-1 rounded-md text-gray-400">...</span>
                        </li>
                    @endif
                @endif

                {{-- Page Numbers --}}
                @for ($page = $start; $page <= $end; $page++)
                    @if ($page == $paginator->currentPage())
                        <li aria-current="page">
                            <span class="px-3 py-1 rounded-md bg-blue-500 text-white">{{ $page }}</span>
                        </li>
                    @else
                        <li>
                            <a href="#" data-page="{{ $page }}" 
                               class="px-3 py-1 rounded-md text-gray-700 hover:bg-gray-100 product-pagination-link">{{ $page }}</a>
                        </li>
                    @endif
                @endfor

                {{-- Last Page Link --}}
                @if ($end < $paginator->lastPage())
                    @if ($end < $paginator->lastPage() - 1)
                        <li aria-disabled="true">
                            <span class="px-3 py-1 rounded-md text-gray-400">...</span>
                        </li>
                    @endif
                    <li>
                        <a href="#" data-page="{{ $paginator->lastPage() }}" 
                           class="px-3 py-1 rounded-md text-gray-700 hover:bg-gray-100 product-pagination-link">{{ $paginator->lastPage() }}</a>
                    </li>
                @endif

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li>
                        <a href="#" data-page="{{ $paginator->currentPage() + 1 }}" 
                           class="px-3 py-1 rounded-md text-gray-700 hover:bg-gray-100 product-pagination-link">Next</a>
                    </li>
                @else
                    <li aria-disabled="true">
                        <span class="px-3 py-1 rounded-md text-gray-400 cursor-not-allowed">Next</span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif