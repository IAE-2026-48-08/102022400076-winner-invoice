@extends('layouts.app')

@section('title', 'Winners')

@section('content')
<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Winners</h1>
        <p class="text-gray-500 mt-1">All auction winners and their invoices</p>
    </div>
    <a href="/checkout" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Checkout
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">
                    <th class="px-6 py-3">ID</th>
                    <th class="px-6 py-3">User</th>
                    <th class="px-6 py-3">Auction Item</th>
                    <th class="px-6 py-3">Winning Bid</th>
                    <th class="px-6 py-3">Invoice Number</th>
                    <th class="px-6 py-3">Invoice Status</th>
                    <th class="px-6 py-3">Won At</th>
                    <th class="px-6 py-3">Action</th>
                </tr>
            </thead>
            <tbody id="winners-table" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-400">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
