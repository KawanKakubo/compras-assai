@extends('layouts.dashboard')

@section('title', 'Editar Usuário')
@section('header_title', 'Editar Usuário')
@section('header_subtitle', 'Atualize as informações de acesso do usuário.')

@section('content')
<div style="max-width: 600px; margin: 0 auto;">
    @if ($errors->any())
        <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <ul style="margin: 0; padding-left: 1.5rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Nome Completo</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:#fff;">
            </div>
            
            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:#fff;">
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Nova Senha (deixe em branco para manter a atual)</label>
                <input type="password" name="password" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:#fff;">
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Nível de Acesso</label>
                <select name="role" required style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:#fff;">
                    <option value="secretaria" {{ $user->role === 'secretaria' ? 'selected' : '' }}>Secretaria</option>
                    <option value="gabinete" {{ $user->role === 'gabinete' ? 'selected' : '' }}>Gabinete</option>
                    <option value="compras" {{ $user->role === 'compras' ? 'selected' : '' }}>Setor de Compras</option>
                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Administrador</option>
                </select>
            </div>

            <div id="secretaria_field" style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Nome da Secretaria</label>
                <input type="text" name="secretaria_name" value="{{ old('secretaria_name', $user->secretaria_name) }}" placeholder="Ex: Secretaria de Saúde" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:#fff;">
                
                <div style="margin-top: 1.2rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Sigla (Ex: SED, SAU, GAB)</label>
                    <input type="text" name="secretaria_acronym" value="{{ old('secretaria_acronym', $user->secretaria_acronym) }}" placeholder="Ex: SED" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:#fff;">
                </div>
            </div>

            <div style="display: flex; gap: 1rem;">
                <a href="{{ route('admin.users.index') }}" class="btn" style="flex: 1; justify-content: center; background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.2);">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary" style="flex: 2; justify-content: center;">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
