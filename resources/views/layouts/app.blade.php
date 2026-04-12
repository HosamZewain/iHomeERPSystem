<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/png" href="/images/icon-192.png">
    <meta name="theme-color" content="#1e40af">
    <meta name="application-name" content="iHome">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="iHome">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="msapplication-TileColor" content="#1e40af">
    <link rel="apple-touch-icon" href="/images/icon-192.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 antialiased" x-data="{ sidebarOpen: false }">
    <div class="flex h-screen overflow-hidden">

        {{-- Desktop Sidebar --}}
        <aside class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0 lg:right-0 z-30">
            <div class="flex flex-col flex-grow bg-primary-800 overflow-y-auto">
                {{-- Logo --}}
                <div class="flex items-center h-16 px-4 bg-primary-900">
                    <span class="text-xl font-bold text-white">iHome</span>
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 px-3 py-4 space-y-1">
                    @include('layouts.partials.nav-links')
                </nav>

                {{-- User info --}}
                <div class="border-t border-primary-700 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-sm font-medium">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        </div>
                        <div class="mr-3 min-w-0 flex-1">
                            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-primary-300 truncate">{{ auth()->user()->role->label() }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-primary-300 hover:text-white" title="تسجيل الخروج">
                                <x-icon name="logout" class="h-5 w-5" />
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Mobile Sidebar Overlay --}}
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 lg:hidden"
             @click="sidebarOpen = false">
            <div class="absolute inset-0 bg-gray-600/75"></div>
        </div>

        {{-- Mobile Sidebar --}}
        <aside x-show="sidebarOpen"
               x-transition:enter="transition ease-in-out duration-200 transform"
               x-transition:enter-start="translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in-out duration-200 transform"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="translate-x-full"
               class="fixed inset-y-0 right-0 z-50 w-64 lg:hidden">
            <div class="flex flex-col h-full bg-primary-800">
                {{-- Close + Logo --}}
                <div class="flex items-center justify-between h-16 px-4 bg-primary-900">
                    <span class="text-xl font-bold text-white">iHome</span>
                    <button @click="sidebarOpen = false" class="text-primary-300 hover:text-white">
                        <x-icon name="x" class="h-6 w-6" />
                    </button>
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    @include('layouts.partials.nav-links')
                </nav>

                {{-- User info --}}
                <div class="border-t border-primary-700 p-4">
                    <div class="flex items-center">
                        <div class="h-8 w-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-sm font-medium">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="mr-3 min-w-0 flex-1">
                            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-primary-300 hover:text-white" title="تسجيل الخروج">
                                <x-icon name="logout" class="h-5 w-5" />
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex flex-col flex-1 lg:pr-64">
            {{-- Top bar (mobile) --}}
            <header class="sticky top-0 z-20 flex items-center h-16 bg-white border-b border-gray-200 px-4 lg:px-6">
                <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700 ml-4">
                    <x-icon name="menu" class="h-6 w-6" />
                </button>
                <h1 class="text-lg font-semibold text-gray-800 truncate">
                    {{ $header ?? config('app.name') }}
                </h1>
                <div class="mr-auto flex items-center space-x-3 space-x-reverse">
                    {{ $actions ?? '' }}
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                {{-- Flash messages --}}
                @if (session('success'))
                    <x-alert type="success" :message="session('success')" />
                @endif
                @if (session('error'))
                    <x-alert type="error" :message="session('error')" />
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
