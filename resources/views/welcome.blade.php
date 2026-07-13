<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ProjectFlow - Modern Enterprise-grade Project Financial Management System. Manage income, expenses, purchases, budgets, and net profit.">
    <meta name="theme-color" content="#ffffff">
    <title>ProjectFlow - Project Financial Management</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234f46e5' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'/%3E%3Cpolyline points='9 22 9 12 15 12 15 22'/%3E%3C/svg%3E">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased">
    <div id="app" class="h-full">
        <!-- Loading splash while Vue boots -->
        <div class="flex flex-col items-center justify-center min-h-screen bg-slate-50">
            <div class="flex items-center space-x-3 mb-6 animate-pulse">
                <!-- Logo Icon -->
                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shadow-md shadow-indigo-200">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <!-- App Name -->
                <span class="text-2xl font-bold tracking-tight text-slate-800">ProjectFlow</span>
            </div>

            <!-- Loading bar -->
            <div class="w-48 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                <div class="w-full h-full bg-indigo-600 rounded-full origin-left animate-[loading_1.5s_infinite_ease-in-out]"></div>
            </div>
        </div>
    </div>

    <style>
        @keyframes loading {
            0% { transform: scaleX(0) translateX(0); }
            50% { transform: scaleX(0.5) translateX(100%); }
            100% { transform: scaleX(0) translateX(300%); }
        }
    </style>
</body>
</html>
