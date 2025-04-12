@extends('layouts.main')
@section('container')

    <div class="flex items-center text-gray-500 text-sm ml-2">
        <i class="ti ti-home text-xl"></i>
        <i class="ti ti-chevron-right text-xl mx-2"></i>
        <span class="text-gray-500 font-semibold text-lg">User</span>
    </div>

    <h1 class="text-gray-700 text-2xl font-bold ml-2">USER</h1>
    <div class="card">
        <div class="card-body">
            <div class="flex justify-end mt-4 mb-4">
                <a href="javascript:;" class="btn btn-primary waves-effect btn-label waves-light" onclick="openUserModal()">
                    <i class="bx bx-plus label-icon"></i> Add Data
                </a>
            </div>

            <!-- Tambahkan Search dan Pagination Controls -->
            <div class="flex justify-between items-center mb-4 flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <input type="text" id="userSearchInput" placeholder="Search..." 
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-full shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <div class="absolute left-3 top-2.5">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center text-sm gap-2">
                    <span class="text-gray-600">Show:</span>
                    <div class="relative">
                        <select id="userPerPage" 
                            class="appearance-none border border-gray-300 rounded-md px-3 pr-8 py-1.5 shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <span class="text-gray-600">entries</span>
                </div>
            </div>

            <!-- Container untuk tabel dan pagination -->
            <div class="relative overflow-x-auto" id="userTableContainer">
                @include('users.partials.table', ['users' => $users])
            </div>

            <div class="mt-4" id="userPaginationLinks">
                @if(isset($users))
                    @include('users.partials.pagination', [
                        'paginator' => $users,
                        'entries_info' => "Showing {$users->firstItem()} to {$users->lastItem()} of {$users->total()} entries"
                    ])
                @endif
            </div>
        </div>
    </div>



    <!-- Modal Add/Edit Data -->
    <div id="dataModal" class="fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-50 hidden z-[999]">
        <div class="bg-white rounded-lg p-6 w-full sm:w-96 max-w-md">
            <div class="flex justify-between items-center">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Add User</h3>
                <button type="button" onclick="closeUserModal()"
                    class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <form id="userForm" action="{{ route('users.save') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mt-4">
                    <label for="email" class="block text-sm font-medium text-gray-900">Email</label>
                    <input type="email" id="email" name="email"
                        class="mt-1 p-2 w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>
                <div class="mt-4">
                    <label for="password" class="block text-sm font-medium text-gray-900">Password</label>
                    <input type="password" id="password" name="password"
                        class="mt-1 p-2 w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>
                <div class="mt-4">
                    <label for="name" class="block text-sm font-medium text-gray-900">Name</label>
                    <input type="text" id="name" name="name"
                        class="mt-1 p-2 w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>
                <div class="mt-4">
                    <label for="role" class="block text-sm font-medium text-gray-900">Role</label>
                    <select id="role" name="role"
                        class="mt-1 p-2 w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <option value="employee">Employee</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeUserModal()"
                        class="px-4 py-2 bg-gray-500 text-white rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg">Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection
