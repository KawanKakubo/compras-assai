@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')
@section('header_title', 'Visão Geral do Sistema')
@section('header_subtitle', 'Bem-vindo de volta, Administrador.')

@section('content')
<div class="stats-grid">
    <div class="card">
        <p class="card-title">Total de Usuários</p>
        <p class="card-value">{{ $usersCount }}</p>
    </div>
    <div class="card">
        <p class="card-title">Secretarias Ativas</p>
        <p class="card-value">{{ $secretariasCount }}</p>
    </div>
    <div class="card">
        <p class="card-title">Novos Usuários (Hoje)</p>
        <p class="card-value">0</p>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3>Usuários Recentes</h3>
        <a href="{{ route('admin.users.index') }}" class="btn btn-primary">Ver Todos</a>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Nível</th>
                    <th>Secretaria</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentUsers as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge {{ $user->role === 'admin' ? 'badge-success' : 'badge-pending' }}">
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
@endsection
