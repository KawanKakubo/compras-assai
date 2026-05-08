<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') | Compras Assaí</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <style>
        :root {
            --primary: #0061ff;
            --secondary: #2563eb;
            --dark-bg: #f8fafc;
            --card-bg: #ffffff;
            --sidebar-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: rgba(15, 23, 42, 0.08);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow: 0 10px 30px rgba(15,23,42,.05);
            --nav-hover-bg: rgba(0, 97, 255, 0.05);
            --nav-hover-color: var(--primary);
        }

        [data-theme="dark"] {
            --secondary: #60efff;
            --dark-bg: #0f172a;
            --card-bg: #1e293b;
            --sidebar-bg: #020617;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
            --shadow: 0 10px 30px rgba(0,0,0,.4);
            --nav-hover-bg: rgba(255, 255, 255, 0.05);
            --nav-hover-color: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: var(--dark-bg);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            transition: background-color 0.3s, color 0.3s;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
            color: var(--text-main);
            transition: background-color 0.3s, border-color 0.3s, color 0.3s;
        }

        .sidebar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-main);
            transition: color 0.3s;
        }

        .sidebar-brand span {
            color: var(--secondary);
            transition: color 0.3s;
        }

        .nav-menu {
            list-style: none;
            flex-grow: 1;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 1rem 1.2rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background: var(--nav-hover-bg);
            color: var(--nav-hover-color);
        }

        .nav-link.active {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: #fff;
        }

        .nav-link i {
            font-size: 1.2rem;
        }

        /* Main Content */
        .main-content {
            flex-grow: 1;
            margin-left: 280px;
            padding: 2rem 3rem;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--card-bg);
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            border: 1px solid var(--border);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-title {
            color: var(--text-muted);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
        }

        .card-value {
            font-size: 2rem;
            font-weight: 700;
        }

        /* Buttons */
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: #fff;
        }

        .btn-primary:hover {
            box-shadow: 0 10px 20px -5px rgba(0, 97, 255, 0.4);
        }

        /* Tables */
        .table-container {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 1.5rem;
            color: var(--text-muted);
            font-weight: 600;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .badge-success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        /* ── Floating Theme Toggle ────────────────── */
        .theme-toggle-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--text-main);
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

        @yield('styles')
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            Compras <span>Assaí</span>
        </div>
        <ul class="nav-menu">
            @if(auth()->user()->isAdmin())
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-line"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-users"></i> Gerenciar Usuários
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.secretarias.index') }}" class="nav-link {{ request()->routeIs('admin.secretarias.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-building-flag"></i> Gerenciar Secretarias
                    </a>
                </li>
            @elseif(auth()->user()->isSecretaria())
                <li class="nav-item">
                    <a href="{{ route('secretaria.dashboard') }}" class="nav-link {{ request()->routeIs('secretaria.dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-house"></i> Início
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('planning.module-one.create') }}" class="nav-link">
                        <i class="fa-solid fa-file-circle-plus"></i> Nova Requisição
                    </a>
                </li>
            @elseif(auth()->user()->isGabinete())
                <li class="nav-item">
                    <a href="{{ route('gabinete.dashboard') }}" class="nav-link {{ request()->routeIs('gabinete.dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-building-columns"></i> Gabinete
                    </a>
                </li>
            @elseif(auth()->user()->isCompras())
                <li class="nav-item">
                    <a href="{{ route('compras.dashboard') }}" class="nav-link {{ request()->routeIs('compras.dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-cart-shopping"></i> Setor de Compras
                    </a>
                </li>
            @endif
        </ul>

        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="nav-link" style="background:none; border:none; width:100%; cursor:pointer;">
                    <i class="fa-solid fa-right-from-bracket"></i> Sair do Sistema
                </button>
            </form>
        </div>
    </div>

    <main class="main-content">
        <header>
            <div>
                <h2 style="font-size: 1.8rem;">@yield('header_title')</h2>
                <p style="color: var(--text-muted);">@yield('header_subtitle')</p>
            </div>
            <div class="user-profile">
                <div class="user-avatar">{{ substr(auth()->user()->name, 0, 1) }}</div>
                <div>
                    <p style="font-weight: 600; font-size: 0.9rem;">{{ auth()->user()->name }}</p>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">{{ ucfirst(auth()->user()->role) }}</p>
                </div>
            </div>
        </header>

        @if(session('success'))
            <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid rgba(16, 185, 129, 0.2);">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>

    @yield('scripts')

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
</body>
</html>
