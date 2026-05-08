@extends('layouts.dashboard')

@section('title', 'Gerenciar Secretarias')
@section('header_title', 'Gestão de Secretarias')
@section('header_subtitle', 'Cadastre, edite e organize os órgãos e secretarias do município.')

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

@if (session('success'))
    <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid rgba(16, 185, 129, 0.2);">
        {{ session('success') }}
    </div>
@endif

<div style="display: grid; grid-template-columns: 1fr 380px; gap: 2rem;">
    <!-- Secretarias List -->
    <div class="card">
        <h3>Secretarias Cadastradas</h3>
        <br>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">Sigla</th>
                        <th>Nome da Secretaria</th>
                        <th>Secretário Responsável</th>
                        <th style="text-align: center; width: 100px;">Elaboradores</th>
                        <th style="width: 120px; text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($secretarias as $secretaria)
                    <tr>
                        <td>
                            <span class="badge" style="background: rgba(0, 97, 255, 0.1); color: var(--primary); padding: 0.5rem 0.8rem; font-weight: 700; border-radius: 8px;">
                                {{ $secretaria->acronym }}
                            </span>
                        </td>
                        <td>
                            <strong style="font-size: 1rem; color: var(--text-main);">{{ $secretaria->name }}</strong>
                        </td>
                        <td>
                            @php
                                $secretary = $secretaria->users->firstWhere('role', 'secretario');
                            @endphp
                            @if($secretary)
                                <span style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-user-tie" style="color: var(--primary); font-size: 0.9rem;"></i>
                                    {{ $secretary->name }}
                                </span>
                            @else
                                <span class="badge badge-pending">Sem secretário</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @php
                                $membersCount = $secretaria->users->where('role', 'elaborador')->count();
                            @endphp
                            <span class="badge" style="background: rgba(148, 163, 184, 0.1); color: var(--text-muted); font-size: 0.9rem; padding: 0.4rem 0.8rem; border-radius: 30px;">
                                <i class="fa-solid fa-users" style="margin-right: 4px;"></i> {{ $membersCount }}
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <a href="{{ route('admin.secretarias.edit', $secretaria) }}" class="btn" style="padding: 0.4rem 0.8rem; background: rgba(37, 99, 235, 0.1); color: #2563eb; border: 1px solid rgba(37, 99, 235, 0.2);">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('admin.secretarias.destroy', $secretaria) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta secretaria? Todos os usuários vinculados serão desassociados.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn" style="padding: 0.4rem 0.8rem; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2);">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                            Nenhuma secretaria cadastrada no sistema.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Secretaria Form -->
    <div class="card" style="height: fit-content;">
        <h3>Nova Secretaria</h3>
        <br>
        <form action="{{ route('admin.secretarias.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Nome da Secretaria</label>
                <input type="text" name="name" required placeholder="Ex: Secretaria de Saúde" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:var(--text-main); transition: var(--transition);">
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Sigla / Acrônimo</label>
                <input type="text" name="acronym" required placeholder="Ex: SAU" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:var(--text-main); transition: var(--transition); text-transform: uppercase;">
                <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 0.4rem;">
                    A sigla será usada para compor o código de referência das requisições de compra.
                </small>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 1rem;">
                <i class="fa-solid fa-circle-plus"></i> Cadastrar Secretaria
            </button>
        </form>
    </div>
</div>
@endsection
