@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">New Checkout</h1>
    <p class="text-gray-500 mt-1">Process a winner checkout for an auction item</p>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form id="checkout-form" class="space-y-6">
            <!-- Token Check Alert -->
            <div id="token-alert" class="hidden p-4 rounded-lg text-sm"></div>

            <!-- Auction Item -->
            <div>
                <label for="auction_item_id" class="block text-sm font-medium text-gray-700 mb-1">Auction Item</label>
                <select id="auction_item_id" name="auction_item_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="">Loading items...</option>
                </select>
            </div>

            <!-- User -->
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                <select id="user_id" name="user_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="">Loading users...</option>
                </select>
            </div>

            <!-- Winning Bid -->
            <div>
                <label for="winning_bid" class="block text-sm font-medium text-gray-700 mb-1">Winning Bid (IDR)</label>
                <input type="number" id="winning_bid" name="winning_bid" min="0" step="0.01" required
                       placeholder="e.g. 5000000"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <p class="mt-1 text-xs text-gray-400" id="bid-preview">—</p>
            </div>

            <!-- Submit -->
            <button type="submit" id="submit-btn"
                    class="w-full px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                Process Checkout
            </button>
        </form>
    </div>
</div>
@endsection
