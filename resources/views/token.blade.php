@extends('layouts.app')

@section('title', 'Token')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">JWT Token</h1>
    <p class="text-gray-500 mt-1">Manage your SSO JWT authentication token for the API</p>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form id="token-form" class="space-y-6">
            <div>
                <label for="jwt-input" class="block text-sm font-medium text-gray-700 mb-1">JWT Token</label>
                <textarea id="jwt-input" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm font-mono"
                          placeholder="Paste your JWT token here..."></textarea>
                <p class="mt-1 text-xs text-gray-400">Token will be stored in your browser and sent as Bearer token to authenticated API endpoints.</p>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                    Save Token
                </button>
                <button type="button" id="clear-token-btn"
                        class="px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors text-sm font-medium">
                    Clear Token
                </button>
            </div>

            <div id="token-status-msg" class="hidden text-sm font-medium"></div>
        </form>
    </div>

    <!-- Token Info -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h3 class="text-sm font-semibold text-blue-800 mb-2">About JWT Token</h3>
        <p class="text-sm text-blue-700 leading-relaxed">
            The checkout endpoint requires a valid SSO JWT token for authentication.
            The JWT should be signed with HS256 algorithm and contain at least an <code class="bg-blue-100 px-1 rounded">email</code> claim.
            Tokens are stored locally in your browser's localStorage.
        </p>
    </div>
</div>
@endsection
