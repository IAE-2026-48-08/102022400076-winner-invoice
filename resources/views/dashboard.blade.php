@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@if (session('success'))
    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
        {{ session('success') }}
    </div>
@endif

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
    <p class="text-gray-500 mt-1">Overview of Winner Invoice system</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">SSO IAE Session</p>
            <h2 class="mt-1 text-lg font-semibold text-gray-900">{{ $ssoUser['email'] ?? 'Email tidak tersedia' }}</h2>
        </div>
        <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $receiptNumber ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
            SOAP Audit: {{ $auditStatus }}
        </span>
    </div>

    <dl class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-3">
        <div>
            <dt class="text-sm font-medium text-gray-500">JWT Token</dt>
            <dd class="mt-1 break-all font-mono text-sm text-gray-900">{{ $maskedToken }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500">Status SOAP Audit</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $auditMessage ?? $auditStatus }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500">Receipt Number</dt>
            <dd class="mt-1 break-all font-mono text-sm text-gray-900">{{ $receiptNumber ?? '-' }}</dd>
        </div>
    </dl>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Winners</p>
                <p id="stat-total-winners" class="text-3xl font-bold text-gray-900 mt-1">—</p>
            </div>
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Invoices</p>
                <p id="stat-total-invoices" class="text-3xl font-bold text-gray-900 mt-1">—</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                <p id="stat-total-revenue" class="text-3xl font-bold text-gray-900 mt-1">—</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Pending Invoices</p>
                <p id="stat-pending-invoices" class="text-3xl font-bold text-gray-900 mt-1">—</p>
            </div>
            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Recent Winners -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Recent Winners</h2>
        <a href="/winners" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View all &rarr;</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-3">Winner</th>
                    <th class="px-6 py-3">Item</th>
                    <th class="px-6 py-3">Bid</th>
                    <th class="px-6 py-3">Invoice</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Date</th>
                </tr>
            </thead>
            <tbody id="recent-winners-table" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-400">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
