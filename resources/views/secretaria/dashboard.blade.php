@extends('layouts.dashboard')

@section('title', 'Dashboard Secretaria')
@section('header_title', 'Minhas Requisições')
@section('header_subtitle', 'Acompanhe o status de suas solicitações de compra.')

@section('content')
<div class="stats-grid">
    <div class="card">
        <p class="card-title">Rascunhos</p>
        <p class="card-value">{{ $stats['draft'] }}</p>
    </div>
    <div class="card">
        <p class="card-title">Assinados</p>
        <p class="card-value">{{ $stats['signed'] }}</p>
    </div>
    <div class="card">
        <p class="card-title">Em Análise</p>
        <p class="card-value">{{ $stats['analysis'] }}</p>
    </div>
    <div class="card">
        <p class="card-title">Devolvidos/Rejeitados</p>
        <p class="card-value" style="color: var(--danger);">{{ $stats['returned'] }}</p>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3>Solicitações da Secretaria</h3>
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
                        @php
                            $statusClasses = [
                                'rascunho' => 'badge-secondary',
                                'assinado' => 'badge-info',
                                'em_analise' => 'badge-pending',
                                'aprovado_compras' => 'badge-success',
                                'rejeitado' => 'badge-danger',
                                'devolvido' => 'badge-warning',
                            ];
                            $statusLabels = [
                                'rascunho' => 'Rascunho',
                                'assinado' => 'Assinado',
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
                    <td>
                        <a href="{{ route('planning.module-one.show', $req->id) }}" class="nav-link" style="padding: 0.5rem; display: inline-flex;" title="Visualizar">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        @if($req->canBeEditedBy(auth()->user()))
                        <a href="{{ route('planning.module-one.create', ['edit' => $req->id]) }}" class="nav-link" style="padding: 0.5rem; display: inline-flex; color: var(--warning);" title="Editar">
                            <i class="fa-solid fa-pencil"></i>
                        </a>
                        <form action="{{ route('planning.module-one.destroy', $req->id) }}" method="POST" style="display: inline-flex;" onsubmit="return confirm('Tem certeza que deseja inativar esta demanda?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="nav-link" style="padding: 0.5rem; border: none; background: none; color: var(--danger); cursor: pointer;" title="Inativar">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                        @endif
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
