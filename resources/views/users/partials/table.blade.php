<table class="text-left w-full whitespace-nowrap text-sm text-gray-500">
    <thead>
        <tr class="text-sm">
            <th class="p-4 font-semibold">#</th>
            <th class="p-4 font-semibold">EMAIL</th>
            <th class="p-4 font-semibold">NAMA</th>
            <th class="p-4 font-semibold">ROLE</th>
            <th class="p-4 font-semibold">ACTION</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $user)
            <tr>
                <td class="p-4">
                    <h3 class="font-medium">{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</h3>
                </td>
                <td class="p-4">
                    <h3 class="font-medium">{{ $user->email }}</h3>
                </td>
                <td class="p-4">
                    <h3 class="font-medium">{{ $user->name }}</h3>
                </td>
                <td class="p-4">
                    <h3 class="font-medium text-teal-500 capitalize">{{ $user->role }}</h3>
                </td>
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
                                    onclick="openUserModal({{ json_encode($user) }})"
                                    class="flex gap-2 items-center font-medium px-4 py-2.5 hover:bg-gray-200 text-gray-400">
                                    <p class="text-sm">Edit</p>
                                </a>
                                <a href="javascript:void(0)" onclick="deleteUser('{{ $user->id }}')"
                                    class="flex gap-2 items-center font-medium px-4 py-2.5 hover:bg-gray-200 text-gray-400">
                                    <p class="text-sm">Delete</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="p-4 text-center text-gray-500">User not found.</td>
            </tr>
        @endforelse
    </tbody>
</table>