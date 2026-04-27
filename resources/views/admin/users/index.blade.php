@extends('layouts.dashboard')

@section('title', 'Gerenciar Usuários')
@section('header_title', 'Gestão de Acessos')
@section('header_subtitle', 'Crie e gerencie os usuários das secretarias e setores.')

@section('content')
@if ($errors->any())
    <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid rgba(239, 68, 68, 0.2);">
        <ul style="margin: 0; padding-left: 1.5rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div style="display: grid; grid-template-columns: 1fr 350px; gap: 2rem;">
    <!-- Users List -->
    <div class="card">
        <h3>Usuários Cadastrados</h3>
        <br>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Nível</th>
                        <th>Secretaria</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge {{ $user->isAdmin() ? 'badge-success' : 'badge-pending' }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td>{{ $user->secretaria_name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create User Form -->
    <div class="card">
        <h3>Novo Usuário</h3>
        <br>
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Nome Completo</label>
                <input type="text" name="name" required style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:#fff;">
            </div>
            
            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">E-mail</label>
                <input type="email" name="email" required style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:#fff;">
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Senha Temporária</label>
                <input type="password" name="password" required style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:#fff;">
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Nível de Acesso</label>
                <select name="role" required style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:#fff;">
                    <option value="secretaria">Secretaria</option>
                    <option value="gabinete">Gabinete</option>
                    <option value="compras">Setor de Compras</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>

            <div id="secretaria_field" style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Nome da Secretaria</label>
                <input type="text" name="secretaria_name" placeholder="Ex: Secretaria de Saúde" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:#fff;">
                
                <div style="margin-top: 1.2rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Sigla (Ex: SED, SAU, GAB)</label>
                    <input type="text" name="secretaria_acronym" placeholder="Ex: SED" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:#fff;">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                Criar Usuário
            </button>
        </form>
    </div>
</div>
@endsection
