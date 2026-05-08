@extends('layouts.dashboard')

@section('title', 'Editar Secretaria')
@section('header_title', 'Editar Secretaria')
@section('header_subtitle', 'Atualize os dados e configurações da secretaria.')

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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-building-flag" style="color: var(--primary);"></i>
                Alterar Dados da Secretaria
            </h3>
            <a href="{{ route('admin.secretarias.index') }}" class="btn" style="background: rgba(148, 163, 184, 0.1); color: var(--text-muted); border: 1px solid var(--border); padding: 0.5rem 1rem;">
                <i class="fa-solid fa-arrow-left"></i> Voltar
            </a>
        </div>

        <form action="{{ route('admin.secretarias.update', $secretaria) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Nome da Secretaria</label>
                <input type="text" name="name" value="{{ old('name', $secretaria->name) }}" required style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:var(--text-main);">
            </div>

            <div style="margin-bottom: 2rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Sigla / Acrônimo</label>
                <input type="text" name="acronym" value="{{ old('acronym', $secretaria->acronym) }}" required style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:var(--text-main); text-transform: uppercase;">
                <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 0.4rem;">
                    Atenção: alterar a sigla não modificará retrospectivamente códigos de requisições de compra que já foram salvos.
                </small>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 1rem;">
                <i class="fa-solid fa-floppy-disk"></i> Salvar Alterações
            </button>
        </form>
    </div>
</div>
@endsection
