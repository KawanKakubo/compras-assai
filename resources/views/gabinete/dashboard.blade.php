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
                <option value="em_analise" {{ request('status') == 'em_analise' ? 'selected' : '' }}>Pendentes</option>
                <option value="aprovado_compras" {{ request('status') == 'aprovado_compras' ? 'selected' : '' }}>Aprovadas</option>
                <option value="rejeitado" {{ request('status') == 'rejeitado' ? 'selected' : '' }}>Rejeitadas</option>
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
                        @php
                            $statusClasses = [
                                'em_analise' => 'badge-pending',
                                'aprovado_compras' => 'badge-success',
                                'rejeitado' => 'badge-danger',
                                'devolvido' => 'badge-warning',
                            ];
                            $statusLabels = [
                                'em_analise' => 'Em Análise',
                                'aprovado_compras' => 'Aprovado',
                                'rejeitado' => 'Rejeitado',
                                'devolvido' => 'Devolvido',
                            ];
                        @endphp
                        <span class="badge {{ $statusClasses[$req->status] ?? 'badge-secondary' }}">
                            {{ $statusLabels[$req->status] ?? ucfirst(str_replace('_', ' ', $req->status)) }}
                        </span>
                    </td>
                    <td style="display: flex; gap: 10px;">
                        <a href="{{ route('planning.module-one.show', $req->id) }}" class="btn" style="background: rgba(255,255,255,0.05); padding: 0.5rem 1rem;">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        
                        @if($req->status === 'em_analise')
                        <form action="{{ route('gabinete.approve', $req->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn" title="Aprovar" style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.5rem 1rem;">
                                <i class="fa-solid fa-check"></i>
                            </button>
                        </form>
                        <button type="button" class="btn" title="Rejeitar" 
                                onclick="document.getElementById('reject-form-{{ $req->id }}').style.display = 'block'"
                                style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 0.5rem 1rem;">
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                        <div id="reject-form-{{ $req->id }}" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: var(--card-bg); padding: 2rem; border-radius: 15px; border: 1px solid var(--border); z-index: 1000; width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                            <h4>Justificativa da Rejeição</h4>
                            <form action="{{ route('gabinete.deny', $req->id) }}" method="POST">
                                @csrf
                                <textarea name="justification" required style="width: 100%; height: 100px; background: var(--dark-bg); color: #fff; border: 1px solid var(--border); padding: 10px; border-radius: 8px; margin: 15px 0;" placeholder="Informe o motivo da rejeição..."></textarea>
                                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                    <button type="button" class="btn" onclick="this.parentElement.parentElement.parentElement.style.display = 'none'">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Confirmar Rejeição</button>
                                </div>
                            </form>
                        </div>
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
