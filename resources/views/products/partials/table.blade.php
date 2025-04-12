<table class="text-left w-full whitespace-nowrap text-sm text-gray-500">
    <thead>
        <tr class="text-sm">
            <th class="p-4 font-semibold">#</th>
            <th class="p-4 font-semibold">IMAGE</th>
            <th class="p-4 font-semibold">PRODUCT NAME</th>
            <th class="p-4 font-semibold">PRICE</th>
            <th class="p-4 font-semibold">STOCK</th>
            @if (Auth::check() && Auth::user()->role === 'admin')
            <th class="p-4 font-semibold">ACTION</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse($products as $product)
            <tr>
                <td class="p-4">
                    <h3 class="font-medium">{{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}</h3>
                </td>
                <td class="p-4">
                    @if ($product->images)
                        <img src="{{ asset('storage/' . $product->images) }}"
                            class="w-32 h-32 object-cover rounded-lg">
                    @else
                        <span class="text-gray-500">No Image</span>
                    @endif
                </td>
                <td class="p-4">
                    <h3 class="font-medium">{{ $product->name }}</h3>
                </td>
                <td class="p-4">
                    <h3 class="font-medium">Rp. {{ number_format($product->price) }}</h3>
                </td>
                <td class="p-4">
                    <h3 class="font-medium text-teal-500">{{ $product->stock }}</h3>
                </td>
                @if (Auth::check() && Auth::user()->role === 'admin')
                                <td class="p-4">
                                    <div
                                        class="hs-dropdown relative inline-flex [--placement:bottom-right] sm:[--trigger:hover]">
                                        <a class="relative hs-dropdown-toggle cursor-pointer align-middle rounded-full">
                                            <i class="ti ti-dots-vertical text-2xl text-gray-400"></i>
                                        </a>
                                        <div
                                            class="card hs-dropdown-menu transition-[opacity,margin] rounded-md duration hs-dropdown-open:opacity-100 opacity-0 mt-2 min-w-max w-[150px] hidden z-[12]">
                                            <div class="card-body p-0 py-2">
                                                <a href="javascript:void(0)"
                                                    onclick="openProductModal({{ json_encode($product) }})"
                                                    class="flex gap-2 items-center font-medium px-4 py-2.5 hover:bg-gray-200 text-gray-400">
                                                    <p class="text-sm">Edit</p>
                                                </a>
                                                <a href="javascript:void(0)"
                                                    onclick="openStockModal({{ json_encode($product) }})"
                                                    class="flex gap-2 items-center font-medium px-4 py-2.5 hover:bg-gray-200 text-gray-400">
                                                    <p class="text-sm">Update Stock</p>
                                                </a>


                                                <a href="javascript:void(0)" onclick="deleteProduct('{{ $product->id }}')"
                                                    class="flex gap-2 items-center font-medium px-4 py-2.5 hover:bg-gray-200 text-gray-400">
                                                    <p class="text-sm">Delete</p>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                @endif
            </tr>
        @empty
            <tr>
                <td colspan="6" class="p-4 text-center text-gray-500">No products available</td>
            </tr>
        @endforelse
    </tbody>
</table>