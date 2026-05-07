<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Compras Assaí</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <style>
        :root {
            --primary: #0061ff;
            --secondary: #60efff;
            --bg-color: #f8fafc;
            --text-color: #0f172a;
            --text-muted: #64748b;
            --container-bg: rgba(255, 255, 255, 0.85);
            --container-border: rgba(15, 23, 42, 0.08);
            --input-bg: #ffffff;
            --input-border: rgba(15, 23, 42, 0.12);
            --input-text: #0f172a;
            --glow-opacity: 0.12;
            --shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.1);
            --error: #ef4444;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] {
            --bg-color: #0f172a;
            --text-color: #ffffff;
            --text-muted: #94a3b8;
            --container-bg: rgba(30, 41, 59, 0.4);
            --container-border: rgba(255, 255, 255, 0.1);
            --input-bg: rgba(255, 255, 255, 0.05);
            --input-border: rgba(255, 255, 255, 0.1);
            --input-text: #ffffff;
            --glow-opacity: 0.25;
            --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: var(--bg-color);
            color: var(--text-color);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            transition: background-color 0.3s, color 0.3s;
        }

        /* Animated Background */
        body::before, body::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            filter: blur(100px);
            z-index: -1;
            opacity: var(--glow-opacity);
            animation: float 20s infinite alternate;
        }

        body::after {
            right: -100px;
            bottom: -100px;
            animation-delay: -10s;
        }

        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(100px, 100px); }
        }

        .login-container {
            background: var(--container-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--container-border);
            padding: 3rem;
            border-radius: 2rem;
            width: 100%;
            max-width: 450px;
            box-shadow: var(--shadow);
            animation: fadeIn 1s ease-out;
            transition: background 0.3s, border-color 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo h1 {
            color: var(--text-color);
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -1px;
        }

        .logo h1 span {
            color: var(--secondary);
        }

        .logo p {
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            padding: 1rem 1.2rem;
            border-radius: 1rem;
            color: var(--input-text);
            font-size: 1rem;
            transition: all 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--input-bg);
            box-shadow: 0 0 0 4px rgba(0, 97, 255, 0.2);
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: #fff;
            border: none;
            padding: 1rem;
            border-radius: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(0, 97, 255, 0.5);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            color: var(--error);
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .footer {
            margin-top: 2rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        /* ── Floating Theme Toggle ────────────────── */
        .theme-toggle-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--container-bg);
            border: 1px solid var(--container-border);
            color: var(--text-color);
            box-shadow: var(--shadow);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            transition: var(--transition);
            backdrop-filter: blur(8px);
        }

        .theme-toggle-btn:hover {
            transform: scale(1.1) rotate(15deg);
            border-color: var(--primary);
        }

        .theme-toggle-btn svg {
            width: 20px;
            height: 20px;
            position: absolute;
            transition: transform 0.5s ease, opacity 0.3s ease;
        }

        /* Light mode styles (default) */
        html:not([data-theme="dark"]) .theme-icon-sun {
            opacity: 0;
            transform: rotate(90deg) scale(0);
        }
        html:not([data-theme="dark"]) .theme-icon-moon {
            opacity: 1;
            transform: rotate(0) scale(1);
        }

        /* Dark mode styles */
        html[data-theme="dark"] .theme-icon-sun {
            opacity: 1;
            transform: rotate(0) scale(1);
        }
        html[data-theme="dark"] .theme-icon-moon {
            opacity: 0;
            transform: rotate(-90deg) scale(0);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Compras <span>Assaí</span></h1>
            <p>Sistema Inteligente de Compras</p>
        </div>

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="email">E-mail Institucional</label>
                <div class="input-group">
                    <input type="email" name="email" id="email" placeholder="nome@assai.pr.gov.br" value="{{ old('email') }}" required autofocus>
                </div>
                @error('email')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                </div>
                @error('password')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-login">Acessar Sistema</button>
        </form>

        <div class="footer">
            Prefeitura Municipal de Assaí &copy; {{ date('Y') }}
        </div>
    </div>
</body>

<!-- Floating Theme Toggle Button -->
<button id="theme-toggle-btn" class="theme-toggle-btn" aria-label="Alternar Tema" title="Alternar Tema">
    <!-- Sun icon -->
    <svg class="theme-icon-sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="5"></circle>
        <line x1="12" y1="1" x2="12" y2="3"></line>
        <line x1="12" y1="21" x2="12" y2="23"></line>
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
        <line x1="1" y1="12" x2="3" y2="12"></line>
        <line x1="21" y1="12" x2="23" y2="12"></line>
        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
    </svg>
    <!-- Moon icon -->
    <svg class="theme-icon-moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
    </svg>
</button>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('theme-toggle-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
            });
        }
    });
</script>
</html>
