<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artisan Command Center</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Add heroicons for better UI -->
    <link href="https://unpkg.com/@heroicons/v2/outline/24/solid.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen bg-gray-50/50">
        <div class="p-6 lg:p-8">
            <!-- Header Section -->
            <div class="sm:flex sm:items-center sm:justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Artisan Command Center</h1>
                    <p class="mt-2 text-sm text-gray-600">Manage your Laravel application's maintenance tasks</p>
                </div>
                <a href="{{ route('logout') }}" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow transition duration-150 ease-in-out">
                    Logout
                </a>
            </div>

            <!-- Alerts Section -->
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-r-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Seeder Section -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Database Seeders</h2>
                    <form action="{{ route('artisan.execute') }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label for="seeder" class="block text-sm font-medium text-gray-700">Select Seeder</label>
                                <select name="seeder" id="seeder" 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md shadow-sm">
                                    <option value="">Choose a seeder...</option>
                                    @foreach($seeders as $seeder)
                                        <option value="{{ $seeder }}">{{ $seeder }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" 
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Run Seeder
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Artisan Commands Section -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">System Commands</h2>
                    <div class="space-y-3">
                        @foreach($commands as $command => $description)
                            <form action="{{ route('artisan.execute') }}" method="POST">
                                @csrf
                                <input type="hidden" name="command" value="{{ $command }}">
                                <button type="submit" 
                                    class="w-full group p-3 flex justify-between items-center rounded-lg hover:bg-gray-50 transition-colors duration-150">
                                    <div class="flex-1">
                                        <h3 class="text-sm font-medium text-gray-900">{{ $description }}</h3>
                                        <p class="mt-1 text-xs text-gray-500">{{ $command }}</p>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Command Output Section -->
            @if(session('artisan_output'))
                <div class="mt-6 bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Command Output</h2>
                    <div class="relative">
                        <pre class="p-4 bg-gray-900 rounded-lg text-green-400 font-mono text-sm overflow-x-auto">{{ session('artisan_output') }}</pre>
                        <button onclick="copyToClipboard()" class="absolute top-2 right-2 p-2 bg-gray-800 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function copyToClipboard() {
            const output = document.querySelector('pre').textContent;
            navigator.clipboard.writeText(output).then(() => {
                // You could add a toast notification here
                alert('Output copied to clipboard!');
            });
        }
    </script>
</body>
</html> 