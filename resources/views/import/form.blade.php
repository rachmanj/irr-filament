<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITO Import</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white p-8 border border-gray-200 rounded-lg shadow-md">
            <h1 class="text-2xl font-bold text-center mb-6">Import ITO Documents</h1>

            <div id="response-message" class="mb-4 hidden rounded-md p-4"></div>

            <form id="import-form" action="{{ route('ito.import') }}" method="POST" enctype="multipart/form-data"
                class="space-y-4">
                @csrf

                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Select File</label>
                    <input id="file" name="file" type="file" accept=".xlsx,.xls,.csv" required
                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                    <p class="mt-1 text-sm text-gray-500">Accepted formats: Excel (.xlsx, .xls) or CSV</p>
                </div>

                <div class="flex justify-between items-center pt-4">
                    <a href="{{ route('ito.download-template') }}"
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Download Template
                    </a>

                    <button type="submit" id="submit-btn"
                        class="px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Import
                    </button>
                </div>
            </form>

            <div id="loading" class="hidden mt-4">
                <div class="flex items-center justify-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span>Processing... This may take a minute.</span>
                </div>
                <div class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('import-form');
            const submitBtn = document.getElementById('submit-btn');
            const loading = document.getElementById('loading');
            const progressBar = document.getElementById('progress-bar');
            const responseMessage = document.getElementById('response-message');

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Hide any previous messages
                responseMessage.classList.add('hidden');

                // Show loading indicator
                loading.classList.remove('hidden');
                submitBtn.disabled = true;

                // Start progress animation
                let progress = 0;
                const interval = setInterval(() => {
                    progress += 5;
                    if (progress > 90) {
                        clearInterval(interval);
                    }
                    progressBar.style.width = progress + '%';
                }, 1000);

                // Create FormData
                const formData = new FormData(form);

                // Send AJAX request
                fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        clearInterval(interval);
                        progressBar.style.width = '100%';

                        // Show response message
                        responseMessage.classList.remove('hidden');

                        if (data.success) {
                            responseMessage.classList.add('bg-green-50', 'text-green-800', 'border',
                                'border-green-400');
                            responseMessage.innerHTML = data.message;

                            // Redirect after 2 seconds if a redirect URL is provided
                            if (data.redirect) {
                                setTimeout(() => {
                                    window.location.href = data.redirect;
                                }, 2000);
                            }
                        } else {
                            responseMessage.classList.add('bg-red-50', 'text-red-800', 'border',
                                'border-red-400');
                            responseMessage.innerHTML = data.message;
                        }
                    })
                    .catch(error => {
                        clearInterval(interval);

                        // Show error message
                        responseMessage.classList.remove('hidden');
                        responseMessage.classList.add('bg-red-50', 'text-red-800', 'border',
                            'border-red-400');
                        responseMessage.innerHTML =
                        'An error occurred during import. Please try again.';
                    })
                    .finally(() => {
                        // Hide loading indicator
                        loading.classList.add('hidden');
                        submitBtn.disabled = false;
                    });
            });
        });
    </script>
</body>

</html>
