<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Authentication - Laravel</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center justify-center min-h-screen">
    <div class="w-full max-w-md">
        <!-- Auth Card -->
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
            <!-- Tabs -->
            <div class="flex mb-6 border-b">
                <button id="loginTab" class="px-4 py-2 font-medium text-sm border-b-2 border-blue-500">Login</button>
                <button id="registerTab" class="px-4 py-2 font-medium text-sm text-gray-500">Register</button>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="login-email">Email</label>
                    <input type="email" id="login-email" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="login-password">Password</label>
                    <input type="password" id="login-password" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600">
                    Login
                </button>
            </form>

            <!-- Register Form (Hidden by default) -->
            <form id="registerForm" class="hidden space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1" for="first_name">First Name</label>
                        <input type="text" id="first_name" class="w-full px-3 py-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="last_name">Last Name</label>
                        <input type="text" id="last_name" class="w-full px-3 py-2 border rounded-lg" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="register-email">Email</label>
                    <input type="email" id="register-email" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="register-password">Password</label>
                    <input type="password" id="register-password" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="phone_number">Phone Number</label>
                    <input type="tel" id="phone_number" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="address">Address</label>
                    <input type="text" id="address" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="role">Role</label>
                    <select id="role" class="w-full px-3 py-2 border rounded-lg" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600">
                    Register
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const loginTab = document.getElementById('loginTab');
            const registerTab = document.getElementById('registerTab');
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');

            // Tab switching
            loginTab.addEventListener('click', () => {
                loginTab.classList.add('border-b-2', 'border-blue-500');
                loginTab.classList.remove('text-gray-500');
                registerTab.classList.remove('border-b-2', 'border-blue-500');
                registerTab.classList.add('text-gray-500');
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
            });

            registerTab.addEventListener('click', () => {
                registerTab.classList.add('border-b-2', 'border-blue-500');
                registerTab.classList.remove('text-gray-500');
                loginTab.classList.remove('border-b-2', 'border-blue-500');
                loginTab.classList.add('text-gray-500');
                registerForm.classList.remove('hidden');
                loginForm.classList.add('hidden');
            });

            // Login form submission
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    const response = await fetch('/api/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            email: document.getElementById('login-email').value,
                            password: document.getElementById('login-password').value,
                        }),
                    });

                    const data = await response.json();
                    if (response.ok) {
                        localStorage.setItem('token', data.token);
                        window.location.href = '/dashboard'; // Redirect to dashboard
                    } else {
                        alert(data.message || 'Login failed');
                    }
                } catch (error) {
                    alert('An error occurred during login');
                }
            });

            // Register form submission
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    const response = await fetch('/api/register', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            first_name: document.getElementById('first_name').value,
                            last_name: document.getElementById('last_name').value,
                            email: document.getElementById('register-email').value,
                            password: document.getElementById('register-password').value,
                            phone_number: document.getElementById('phone_number').value,
                            address: document.getElementById('address').value,
                            role: document.getElementById('role').value,
                        }),
                    });

                    const data = await response.json();
                    if (response.ok) {
                        localStorage.setItem('token', data.token);
                        window.location.href = '/dashboard'; // Redirect to dashboard
                    } else {
                        alert(data.message || 'Registration failed');
                    }
                } catch (error) {
                    alert('An error occurred during registration');
                }
            });
        });
    </script>
</body>

</html>