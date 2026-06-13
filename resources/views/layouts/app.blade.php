<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="sso-authenticated" content="{{ session()->has('sso.token') ? '1' : '0' }}">
    <title>@yield('title', 'Winner Invoice') — Winner Invoice</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">

    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-8">
                    <a href="/" class="text-xl font-bold text-indigo-600 tracking-tight">
                        Winner Invoice
                    </a>
                    <div class="hidden md:flex items-center gap-1">
                        <a href="/"
                           class="px-3 py-2 rounded-md text-sm font-medium transition-colors @if(request()->path() === '/') bg-indigo-50 text-indigo-700 @else text-gray-600 hover:text-gray-900 hover:bg-gray-100 @endif">
                            Dashboard
                        </a>
                        <a href="/winners"
                           class="px-3 py-2 rounded-md text-sm font-medium transition-colors @if(request()->is('winners*') && !request()->is('winners/create')) bg-indigo-50 text-indigo-700 @else text-gray-600 hover:text-gray-900 hover:bg-gray-100 @endif">
                            Winners
                        </a>
                        <a href="/checkout"
                           class="px-3 py-2 rounded-md text-sm font-medium transition-colors @if(request()->is('winners/create')) bg-indigo-50 text-indigo-700 @else text-gray-600 hover:text-gray-900 hover:bg-gray-100 @endif">
                            Checkout
                        </a>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if (session()->has('sso.user'))
                        <span class="hidden text-sm text-gray-600 lg:inline">
                            {{ data_get(session('sso.user'), 'email') }}
                        </span>
                    @endif
                    <a href="/token"
                       class="px-3 py-2 rounded-md text-sm font-medium transition-colors @if(request()->is('token')) bg-indigo-50 text-indigo-700 @else text-gray-600 hover:text-gray-900 hover:bg-gray-100 @endif">
                        Token
                    </a>
                    @if (session()->has('sso.token'))
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded-md text-sm font-medium text-red-600 hover:bg-red-50">
                                Logout
                            </button>
                        </form>
                    @endif
                    <span id="token-status" class="text-xs px-2 py-1 rounded-full hidden"></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 z-50 hidden"></div>

</body>
</html>
