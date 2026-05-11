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
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:var(--text-main);">
            </div>
            
            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:var(--text-main);">
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">CPF (Opcional, usado para assinar)</label>
                <input type="text" name="cpf" value="{{ old('cpf', $user->cpf) }}" class="mask-cpf" placeholder="000.000.000-00" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:var(--text-main);">
            </div>

            <div style="border-top: 1px solid var(--border); margin: 1.5rem 0; padding-top: 1rem;">
                <h4 style="margin-bottom: 1rem; color: #2563eb; display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem;">
                    <i class="ph ph-signature"></i> Credenciais LibreSign (Opcional)
                </h4>
                <div style="margin-bottom: 1.2rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Usuário Nextcloud</label>
                    <input type="text" name="libresign_username" value="{{ old('libresign_username', $user->libresign_username) }}" placeholder="ex: kawan.kakubo" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:var(--text-main);">
                </div>
                <div style="margin-bottom: 1.2rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">E-mail Assinante LibreSign</label>
                    <input type="email" name="libresign_signer_account" value="{{ old('libresign_signer_account', $user->libresign_signer_account) }}" placeholder="ex: kawan@assai.pr.gov.br" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:var(--text-main);">
                </div>
                <div style="margin-bottom: 1.2rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Senha de Aplicativo Nextcloud (deixe em branco para manter a atual)</label>
                    <input type="password" name="libresign_password" placeholder="••••••••••••••••" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:var(--text-main);">
                </div>
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Nova Senha (deixe em branco para manter a atual)</label>
                <input type="password" name="password" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:var(--text-main);">
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Nível de Acesso</label>
                <select name="role" required style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:var(--text-main);">
                    <option value="elaborador" {{ $user->role === 'elaborador' ? 'selected' : '' }}>Secretaria (Elaborador)</option>
                    <option value="secretario" {{ $user->role === 'secretario' ? 'selected' : '' }}>Secretaria (Secretário)</option>
                    <option value="gabinete" {{ $user->role === 'gabinete' ? 'selected' : '' }}>Gabinete</option>
                    <option value="compras" {{ $user->role === 'compras' ? 'selected' : '' }}>Setor de Compras</option>
                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Administrador</option>
                </select>
            </div>

            <div id="secretaria_field" style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Secretaria Vinculada</label>
                <select name="secretaria_id" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:var(--text-main);">
                    <option value="">Selecione uma secretaria...</option>
                    @foreach($secretarias as $sec)
                        <option value="{{ $sec->id }}" {{ old('secretaria_id', $user->secretaria_id) == $sec->id ? 'selected' : '' }}>
                            {{ $sec->name }} ({{ $sec->acronym }})
                        </option>
                    @endforeach
                </select>
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

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.querySelector('select[name="role"]');
        const secretariaField = document.getElementById('secretaria_field');

        function toggleSecretaria() {
            const role = roleSelect.value;
            if (role === 'elaborador' || role === 'secretario') {
                secretariaField.style.display = 'block';
            } else {
                secretariaField.style.display = 'none';
            }
        }

        roleSelect.addEventListener('change', toggleSecretaria);
        toggleSecretaria(); // Run initially

        // CPF Input Mask
        const cpfInputs = document.querySelectorAll('.mask-cpf');
        cpfInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let val = e.target.value.replace(/\D/g, '');
                if (val.length > 11) {
                    val = val.substring(0, 11);
                }
                if (val.length > 9) {
                    val = val.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
                } else if (val.length > 6) {
                    val = val.replace(/^(\d{3})(\d{3})(\d{1,3})$/, '$1.$2.$3');
                } else if (val.length > 3) {
                    val = val.replace(/^(\d{3})(\d{1,3})$/, '$1.$2');
                }
                e.target.value = val;
            });
        });
    });
</script>
@endsection
