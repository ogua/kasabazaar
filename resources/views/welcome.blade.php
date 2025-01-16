<x-layouts.app>
    <!-- Top Header Section with Company Logo, Name, and Login Button -->
    <div class="flex items-center justify-between p-4 bg-white shadow-md">
        <!-- Company Logo and Name -->
        <div class="flex items-center">
            <img src="/images/kasabazaar-logo.png" alt="Kasabazar Logo" class="w-12 h-12 mr-2">
            <span class="text-2xl font-bold text-gray-800">Kasabazar</span>
        </div>

        <!-- Login Button -->
        <a href="/admin" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-600">
            Login
        </a>
    </div>

    <!-- Main Content -->
    <div class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="w-full max-w-4xl p-6 text-center bg-white rounded-lg shadow-lg">
            <!-- Logo and Welcome Message -->
            <div class="mb-8">
                <img src="/images/kasabazaar-logo.png" alt="Logo" class="w-24 mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-800">Welcome to Our Services</h1>
            </div>

            <!-- Images Section -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <!-- Import and Export -->
                <div class="p-4 border rounded-lg shadow-sm bg-gray-50">
                    <img src="/images/import-export.png" alt="Import and Export" class="object-cover w-full h-40 mb-4 rounded">
                    <h2 class="text-lg font-semibold text-gray-700">Import and Export</h2>
                    <a href="/images/import-export" class="inline-block px-4 py-2 mt-4 text-white bg-blue-500 rounded hover:bg-blue-600">Import & Export</a>
                </div>

                <!-- Real Estate -->
                <div class="p-4 border rounded-lg shadow-sm bg-gray-50">
                    <img src="/images/real-estate.jpg" alt="Real Estate" class="object-cover w-full h-40 mb-4 rounded">
                    <h2 class="text-lg font-semibold text-gray-700">Real Estate</h2>
                    <a href="http://shipping.kasabazaar.com" class="inline-block px-4 py-2 mt-4 text-white bg-blue-500 rounded hover:bg-blue-600">Real Estate</a>
                </div>

                <!-- Property Management -->
                <div class="p-4 border rounded-lg shadow-sm bg-gray-50">
                    <img src="/images/property-management.jpg" alt="Property Management" class="object-cover w-full h-40 mb-4 rounded">
                    <h2 class="text-lg font-semibold text-gray-700">Property Management</h2>
                    <a href="/property-management" class="inline-block px-4 py-2 mt-4 text-white bg-blue-500 rounded hover:bg-blue-600">Property Management</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
