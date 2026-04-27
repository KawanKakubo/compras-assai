@extends('layouts.dashboard')

@section('title', 'Dashboard Compras')
@section('header_title', 'Setor de Compras')
@section('header_subtitle', 'Gerencie as solicitações aprovadas pelo gabinete.')

@section('content')
<div class="stats-grid">
    <div class="card">
        <p class="card-title">Aguardando Compra</p>
        <p class="card-value">{{ $stats['pending_compras'] }}</p>
    </div>
    <div class="card">
        <p class="card-title">Processos Finalizados</p>
        <p class="card-value">{{ $stats['finalized'] }}</p>
    </div>
</div>

<div class="card">
    <h3>Requisições para Processamento</h3>
    <br>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Secretaria</th>
                    <th>Título</th>
                    <th>Data Aprovação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td><strong>{{ $req->reference_code }}</strong></td>
                    <td>{{ $req->user->secretaria_name ?? $req->user->name }}</td>
                    <td>{{ $req->title }}</td>
                    <td>{{ $req->updated_at->format('d/m/Y') }}</td>
                    <td style="display: flex; gap: 10px;">
                        <a href="{{ route('planning.module-one.show', $req->id) }}" class="btn" style="background: rgba(255,255,255,0.05); padding: 0.5rem 1rem;">
                            <i class="fa-solid fa-eye"></i> Visualizar
                        </a>
                        
                        <form action="{{ route('compras.finalize', $req->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">
                                <i class="fa-solid fa-cart-check"></i> Finalizar Compra
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                        Nenhuma requisição pendente no momento.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
