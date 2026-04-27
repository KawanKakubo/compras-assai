@extends('layouts.dashboard')

@section('title', 'Dashboard Secretaria')
@section('header_title', 'Minhas Requisições')
@section('header_subtitle', 'Acompanhe o status de suas solicitações de compra.')

@section('content')
<div class="stats-grid">
    <div class="card">
        <p class="card-title">Total Enviado</p>
        <p class="card-value">{{ $stats['total'] }}</p>
    </div>
    <div class="card">
        <p class="card-title">Em Análise (Gabinete)</p>
        <p class="card-value">{{ $stats['pending'] }}</p>
    </div>
    <div class="card">
        <p class="card-title">Aprovadas</p>
        <p class="card-value">{{ $stats['approved'] }}</p>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3>Últimas Solicitações</h3>
        <a href="{{ route('planning.module-one.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Nova Solicitação
        </a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
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
                    <td>{{ $req->title }}</td>
                    <td>{{ $req->created_at->format('d/m/Y') }}</td>
                    <td>
                        @if($req->status === 'aguardando_gabinete')
                            <span class="badge badge-pending">Gabinete</span>
                        @elseif($req->status === 'aprovado_gabinete')
                            <span class="badge badge-success">Aprovado</span>
                        @elseif($req->status === 'negado_gabinete')
                            <span class="badge badge-danger">Negado</span>
                        @else
                            <span class="badge">{{ $req->status }}</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('planning.module-one.show', $req->id) }}" class="nav-link" style="padding: 0.5rem; display: inline-flex;">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                        Nenhuma solicitação encontrada. Comece criando uma nova!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
