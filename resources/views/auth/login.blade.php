<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premier Deliveries - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --surface: #ffffff;
            --surface-dark: #1F2937;
            --text: #111827;
            --text-dark: #F9FAFB;
            --text-secondary: #6B7280;
            --text-secondary-dark: #9CA3AF;
            --error: #EF4444;
            --success: #10B981;
            --border: #E5E7EB;
            --border-dark: #374151;
            --focus-ring: rgba(79, 70, 229, 0.2);
        }

        [data-theme="dark"] {
            --primary: #6366F1;
            --primary-dark: #4F46E5;
            --surface: #111827;
            --text: #F9FAFB;
            --text-secondary: #9CA3AF;
            --border: #374151;
            --focus-ring: rgba(99, 102, 241, 0.2);
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--surface);
            color: var(--text);
            transition: background-color 0.3s, color 0.3s;
        }

        .theme-switch {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            padding: 0.5rem;
            border-radius: 0.75rem;
            background-color: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .theme-switch:hover {
            background-color: var(--border);
        }

        .input-group {
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            background-color: var(--surface);
            color: var(--text);
            font-size: 1rem;
            transition: all 0.2s;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--focus-ring);
        }

        .input-group input::placeholder {
            color: var(--text-secondary);
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-primary:focus {
            outline: none;
            box-shadow: 0 0 0 4px var(--focus-ring);
        }

        .btn-primary .spinner {
            display: none;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        .btn-primary.loading {
            color: transparent;
        }

        .btn-primary.loading .spinner {
            display: block;
        }

        .toast-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 50;
            display: flex;
            flex-direction: column-reverse;
            gap: 0.75rem;
            max-width: calc(100% - 3rem);
        }

        .toast {
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            width: 100%;
            max-width: 24rem;
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast-error {
            border-left: 4px solid var(--error);
        }

        .toast-success {
            border-left: 4px solid var(--success);
        }

        .brand-logo {
            width: 4rem;
            height: 4rem;
            background-color: var(--primary);
            border-radius: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .brand-logo:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
            z-index: 1;
        }

        .brand-logo svg {
            width: 2.5rem;
            height: 2.5rem;
            z-index: 2;
        }

        .brand-logo .logo-path {
            stroke: white;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
        }

        .brand-logo .logo-fill {
            fill: white;
        }

        .login-illustration {
            display: none;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 1.5rem;
            padding: 2rem;
        }

        @media (min-width: 1024px) {
            .login-illustration {
                display: block;
            }
        }
    </style>
</head>

<body>
    <button class="theme-switch" onclick="toggleTheme()" aria-label="Toggle theme">
        <span class="theme-icon">🌞</span>
        <span class="theme-text">Light</span>
    </button>

    <div class="toast-container" id="toastContainer"></div>

    <main class="min-h-screen flex items-center justify-center p-6">
        <div class="w-full max-w-5xl grid lg:grid-cols-2 gap-12">
            <div class="login-illustration hidden lg:flex items-center justify-center">
                <svg class="w-full max-w-md" viewBox="0 0 550 500" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Background elements -->
                    <defs>
                        <radialGradient id="firework1" cx="0.5" cy="0.5" r="0.5">
                            <stop offset="0%" stop-color="rgba(255,255,255,0.8)" />
                            <stop offset="100%" stop-color="rgba(255,255,255,0)" />
                        </radialGradient>
                        <radialGradient id="firework2" cx="0.5" cy="0.5" r="0.5">
                            <stop offset="0%" stop-color="rgba(255,255,255,0.6)" />
                            <stop offset="100%" stop-color="rgba(255,255,255,0)" />
                        </radialGradient>
                    </defs>

                    <path fill="rgba(255,255,255,0.05)"
                        d="M50 100 h450 a20 20 0 0 1 20 20 v260 a20 20 0 0 1 -20 20 h-450 a20 20 0 0 1 -20 -20 v-260 a20 20 0 0 1 20 -20 z" />
                    <circle cx="275" cy="250" r="180" fill="rgba(255,255,255,0.03)" />

                    <!-- Fireworks -->
                    <g class="fireworks">
                        <circle class="firework" cx="150" cy="150" r="3" fill="url(#firework1)">
                            <animate attributeName="r" values="0;20;0" dur="3s" repeatCount="indefinite"
                                begin="0s" />
                            <animate attributeName="opacity" values="1;0" dur="3s" repeatCount="indefinite"
                                begin="0s" />
                        </circle>
                        <circle class="firework" cx="400" cy="180" r="3" fill="url(#firework2)">
                            <animate attributeName="r" values="0;15;0" dur="2.5s" repeatCount="indefinite"
                                begin="1s" />
                            <animate attributeName="opacity" values="1;0" dur="2.5s" repeatCount="indefinite"
                                begin="1s" />
                        </circle>
                        <circle class="firework" cx="280" cy="120" r="3" fill="url(#firework1)">
                            <animate attributeName="r" values="0;25;0" dur="4s" repeatCount="indefinite"
                                begin="2s" />
                            <animate attributeName="opacity" values="1;0" dur="4s" repeatCount="indefinite"
                                begin="2s" />
                        </circle>
                    </g>

                    <!-- Airplane -->
                    <g class="airplane" style="animation: flyAcross 15s linear infinite">
                        <path fill="rgba(255,255,255,0.9)" d="M0 0l25 8-2 2-23 2z" />
                        <path fill="rgba(255,255,255,0.9)" d="M25 8l5 2-3 1-4-1z" />
                        <path fill="rgba(255,255,255,0.7)" d="M10 3l3 8-8-2z" />
                        <path fill="rgba(255,255,255,0.6)" d="M18 7l-2-4 6 2z" />
                        <animateMotion dur="15s" repeatCount="indefinite" path="M-50 100 Q 275 50 600 150" />
                    </g>

                    <!-- City skyline -->
                    <path fill="rgba(255,255,255,0.1)" d="M50 380h450v20H50z" />
                    <path fill="rgba(255,255,255,0.08)" d="M80 320h40v80H80z" />
                    <path fill="rgba(255,255,255,0.08)" d="M140 340h30v60h-30z" />
                    <path fill="rgba(255,255,255,0.08)" d="M190 300h50v100h-50z" />
                    <path fill="rgba(255,255,255,0.08)" d="M260 330h40v70h-40z" />
                    <path fill="rgba(255,255,255,0.08)" d="M320 310h35v90h-35z" />
                    <path fill="rgba(255,255,255,0.08)" d="M380 350h45v50h-45z" />
                    <path fill="rgba(255,255,255,0.08)" d="M440 290h40v110h-40z" />

                    <!-- Roads and paths -->
                    <path stroke="rgba(255,255,255,0.2)" stroke-width="2" d="M50 350q200 -80 450 0" fill="none" />
                    <path stroke="rgba(255,255,255,0.1)" stroke-width="1" d="M50 355q200 -80 450 0" fill="none" />
                    <path stroke="rgba(255,255,255,0.1)" stroke-width="1" d="M50 345q200 -80 450 0" fill="none" />

                    <!-- Animated delivery elements -->
                    <g class="delivery-vehicles" style="animation: moveRight 20s linear infinite">
                        <!-- Main delivery truck -->
                        <g transform="translate(100, 360) scale(0.6)">
                            <path fill="rgba(255,255,255,0.9)" d="M10 20h40v20H10z" />
                            <path fill="rgba(255,255,255,0.9)" d="M50 25h15l5 10v5H50z" />
                            <circle fill="rgba(255,255,255,0.9)" cx="20" cy="40" r="5" />
                            <circle fill="rgba(255,255,255,0.9)" cx="60" cy="40" r="5" />
                            <path fill="rgba(255,255,255,0.6)" d="M45 22h8l2 6h-10z" />
                        </g>

                        <!-- Delivery drone -->
                        <g transform="translate(250, 180) scale(0.6)">
                            <path fill="rgba(255,255,255,0.9)" d="M20 20h20v20H20z" />
                            <path fill="rgba(255,255,255,0.9)" d="M10 25l40 0" stroke="rgba(255,255,255,0.9)"
                                stroke-width="2" />
                            <circle fill="rgba(255,255,255,0.9)" cx="10" cy="25" r="5" />
                            <circle fill="rgba(255,255,255,0.9)" cx="50" cy="25" r="5" />
                            <path fill="rgba(255,255,255,0.6)" d="M25 15l10 0l-5 -10z" />
                        </g>

                        <!-- Package boxes -->
                        <g transform="translate(400, 300) scale(0.5)">
                            <path fill="rgba(255,255,255,0.9)" d="M10 10h20v20H10z" />
                            <path fill="rgba(255,255,255,0.7)" d="M15 15h10v10H15z" />
                        </g>
                        <g transform="translate(150, 320) scale(0.4)">
                            <path fill="rgba(255,255,255,0.9)" d="M10 10h15v15H10z" />
                            <path fill="rgba(255,255,255,0.7)" d="M13 13h9v9H13z" />
                        </g>
                    </g>

                    <!-- Connection lines -->
                    <g class="connection-lines">
                        <path stroke="rgba(255,255,255,0.1)" stroke-width="1" stroke-dasharray="5,5"
                            d="M100 200q100 -50 200 0" />
                        <path stroke="rgba(255,255,255,0.1)" stroke-width="1" stroke-dasharray="5,5"
                            d="M300 200q100 50 150 -50" />
                        <path stroke="rgba(255,255,255,0.1)" stroke-width="1" stroke-dasharray="5,5"
                            d="M200 150q-50 100 100 150" />
                    </g>

                    <!-- Location markers -->
                    <g class="location-markers">
                        <circle cx="100" cy="200" r="5" fill="rgba(255,255,255,0.3)" />
                        <circle cx="300" cy="200" r="5" fill="rgba(255,255,255,0.3)" />
                        <circle cx="450" cy="150" r="5" fill="rgba(255,255,255,0.3)" />
                        <circle cx="200" cy="150" r="5" fill="rgba(255,255,255,0.3)" />
                        <circle cx="300" cy="300" r="5" fill="rgba(255,255,255,0.3)" />
                    </g>

                    <style>
                        @keyframes moveRight {
                            from {
                                transform: translateX(-100%);
                            }

                            to {
                                transform: translateX(100%);
                            }
                        }

                        @keyframes flyAcross {
                            from {
                                transform: translateX(-100%) rotate(10deg);
                            }

                            50% {
                                transform: translateX(0) rotate(-5deg);
                            }

                            to {
                                transform: translateX(100%) rotate(10deg);
                            }
                        }

                        .delivery-vehicles {
                            animation: moveRight 20s linear infinite;
                        }

                        .firework {
                            transform-origin: center;
                            opacity: 0;
                        }

                        .airplane {
                            transform-origin: center;
                        }
                    </style>
                </svg>
            </div>

            <div class="w-full max-w-md mx-auto">
                <div class="brand-logo mx-auto">
                    <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                        <!-- Fast delivery truck with modern design -->
                        <path class="logo-path" d="M8 32h32v16H8z" /> <!-- Base of truck -->
                        <path class="logo-path" d="M40 32h12l4 8v8H40z" /> <!-- Truck cabin -->
                        <circle class="logo-path" cx="16" cy="48" r="4" /> <!-- Left wheel -->
                        <circle class="logo-path" cx="48" cy="48" r="4" /> <!-- Right wheel -->
                        <path class="logo-path" d="M8 32V24h24" /> <!-- Hood -->
                        <path class="logo-path" d="M3 28h5" /> <!-- Left speed line -->
                        <path class="logo-path" d="M5 24h5" /> <!-- Middle speed line -->
                        <path class="logo-path" d="M4 20h5" /> <!-- Right speed line -->
                        <path class="logo-fill" d="M38 28h4l2 4h-6z" /> <!-- Window -->
                        <!-- P monogram -->
                        <path class="logo-path" d="M14 28v-8h6c2 0 4 1 4 4s-2 4-4 4h-6" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-center mb-2">Welcome back</h1>
                <p class="text-center text-sm text-secondary mb-8">Sign in to access the API documentation</p>

                <form id="loginForm" class="space-y-6" action="{{ route('login.submit') }}" method="POST">
                    @csrf
                    <div class="input-group">
                        <label for="email">Email address</label>
                        <input id="email" name="email" type="email" autocomplete="email" required
                            placeholder="Enter your email" aria-label="Email address">
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password"
                            required placeholder="Enter your password" aria-label="Password">
                    </div>

                    <button type="submit" class="btn-primary w-full">
                        <span class="spinner"></span>
                        <span class="btn-text">Sign in</span>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const themeSwitch = document.querySelector('.theme-switch');
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            // Update theme switch content
            const icon = themeSwitch.querySelector('.theme-icon');
            const text = themeSwitch.querySelector('.theme-text');
            icon.textContent = newTheme === 'dark' ? '🌙' : '🌞';
            text.textContent = newTheme === 'dark' ? 'Dark' : 'Light';
        }

        // Initialize theme
        const savedTheme = localStorage.getItem('theme') ||
            (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', savedTheme);

        // Update initial theme switch content
        const themeSwitch = document.querySelector('.theme-switch');
        const icon = themeSwitch.querySelector('.theme-icon');
        const text = themeSwitch.querySelector('.theme-text');
        icon.textContent = savedTheme === 'dark' ? '🌙' : '🌞';
        text.textContent = savedTheme === 'dark' ? 'Dark' : 'Light';

        // Form handling
        const loginForm = document.getElementById('loginForm');
        loginForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.btn-primary');
            submitBtn.classList.add('loading');
        });

        function showToast(title, message, type = 'error') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;

            toast.innerHTML = `
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 ${type === 'error' ? 'text-error' : 'text-success'}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium">${title}</p>
                    <p class="text-sm text-secondary mt-1">${message}</p>
                </div>
                <button class="flex-shrink-0 ml-4" onclick="this.parentElement.remove()">
                    <svg class="w-4 h-4 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
                    </svg>
                </button>
            `;

            container.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('show'));

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        @if ($errors->any())
            showToast('Error', '{{ $errors->first() }}');
        @endif
    </script>
</body>

</html>
