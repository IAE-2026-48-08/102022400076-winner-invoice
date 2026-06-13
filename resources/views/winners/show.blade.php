@extends('layouts.app')

@section('title', 'Winner Detail')

@section('content')
<div class="mb-8 flex items-center justify-between">
    <div>
        <a href="/winners" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">&larr; Back to Winners</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Winner Detail</h1>
    </div>
</div>

<div id="winner-detail" class="space-y-6">
    <div class="text-center text-gray-400 py-12">Loading...</div>
</div>

<div id="winner-error" class="hidden">
    <div class="bg-red-50 border border-red-200 rounded-xl p-8 text-center">
        <svg class="w-12 h-12 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        <h2 class="text-lg font-semibold text-red-800 mb-2">Winner Not Found</h2>
        <p class="text-red-600" id="error-message">The requested winner could not be found.</p>
        <a href="/winners" class="mt-4 inline-block text-sm font-medium text-red-700 hover:text-red-900">&larr; Back to Winners</a>
    </div>
</div>
@endsection
