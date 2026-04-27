<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | G-Proc Assaí</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0061ff;
            --secondary: #60efff;
            --dark: #0f172a;
            --light: #f8fafc;
            --error: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: var(--dark);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
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
            opacity: 0.3;
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
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 3rem;
            border-radius: 2rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 1s ease-out;
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
            color: #fff;
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -1px;
        }

        .logo h1 span {
            color: var(--secondary);
        }

        .logo p {
            color: #94a3b8;
            margin-top: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: #cbd5e1;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 1.2rem;
            border-radius: 1rem;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.1);
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
            color: #64748b;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>G-<span>Proc</span></h1>
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
</html>
