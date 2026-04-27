@extends('layouts.dashboard')

@section('title', 'Dashboard Gabinete')
@section('header_title', 'Central de Aprovações')
@section('header_subtitle', 'Analise e despache as solicitações das secretarias.')

@section('content')
<div class="stats-grid">
    <div class="card">
        <p class="card-title">Aguardando Gabinete</p>
        <p class="card-value">{{ $stats['pending'] }}</p>
    </div>
    <div class="card">
        <p class="card-title">Aprovadas (Total)</p>
        <p class="card-value">{{ $stats['approved'] }}</p>
    </div>
    <div class="card">
        <p class="card-title">Negadas (Total)</p>
        <p class="card-value">{{ $stats['denied'] }}</p>
    </div>
</div>

<div class="card" style="margin-bottom: 2rem;">
    <form action="{{ route('gabinete.dashboard') }}" method="GET" style="display: flex; gap: 1.5rem; align-items: flex-end;">
        <div style="flex-grow: 1;">
            <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Filtrar por Secretaria</label>
            <select name="secretaria_id" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:#fff;">
                <option value="">Todas as Secretarias</option>
                @foreach($secretarias as $sec)
                    <option value="{{ $sec->id }}" {{ request('secretaria_id') == $sec->id ? 'selected' : '' }}>
                        {{ $sec->secretaria_name ?? $sec->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">Status</label>
            <select name="status" style="width:100%; background:var(--dark-bg); border:1px solid var(--border); padding:0.8rem; border-radius:10px; color:#fff;">
                <option value="aguardando_gabinete" {{ request('status') == 'aguardando_gabinete' ? 'selected' : '' }}>Pendentes</option>
                <option value="aprovado_gabinete" {{ request('status') == 'aprovado_gabinete' ? 'selected' : '' }}>Aprovadas</option>
                <option value="negado_gabinete" {{ request('status') == 'negado_gabinete' ? 'selected' : '' }}>Negadas</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-filter"></i> Filtrar
        </button>
    </form>
</div>

<div class="card">
    <h3>Solicitações</h3>
    <br>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Secretaria</th>
                    <th>Título</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td><strong>{{ $req->reference_code }}</strong></td>
                    <td>{{ $req->user->secretaria_name ?? $req->user->name }}</td>
                    <td>{{ $req->title }}</td>
                    <td>{{ $req->created_at->format('d/m/Y') }}</td>
                    <td>
                        <span class="badge {{ $req->status === 'aguardando_gabinete' ? 'badge-pending' : ($req->status === 'aprovado_gabinete' ? 'badge-success' : 'badge-danger') }}">
                            {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                        </span>
                    </td>
                    <td style="display: flex; gap: 10px;">
                        <a href="{{ route('planning.module-one.show', $req->id) }}" class="btn" style="background: rgba(255,255,255,0.05); padding: 0.5rem 1rem;">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        
                        @if($req->status === 'aguardando_gabinete')
                        <form action="{{ route('gabinete.approve', $req->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn" style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.5rem 1rem;">
                                <i class="fa-solid fa-check"></i>
                            </button>
                        </form>
                        <form action="{{ route('gabinete.deny', $req->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 0.5rem 1rem;">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                        Nenhuma solicitação encontrada para os filtros aplicados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
