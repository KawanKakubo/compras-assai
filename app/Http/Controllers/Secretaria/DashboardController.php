<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Planning\ProcurementRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        
        // Se for um usuário de secretaria, ele vê todas as solicitações daquela secretaria
        $query = ProcurementRequest::where('status', '!=', ProcurementRequest::STATUS_INATIVO);
        
        if ($user->secretaria_id) {
            $query->whereHas('user', function($q) use ($user) {
                $q->where('secretaria_id', $user->secretaria_id);
            });
        } elseif ($user->secretaria_acronym) {
            $query->whereHas('user', function($q) use ($user) {
                $q->where('secretaria_acronym', $user->secretaria_acronym);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        $requests = $query->latest()->get();

        $stats = [
            'total' => $requests->count(),
            'draft' => $requests->where('status', ProcurementRequest::STATUS_RASCUNHO)->count(),
            'signed' => $requests->where('status', ProcurementRequest::STATUS_ASSINADO)->count(),
            'analysis' => $requests->where('status', ProcurementRequest::STATUS_EM_ANALISE)->count(),
            'approved' => $requests->where('status', ProcurementRequest::STATUS_APROVADO_COMPRAS)->count(),
            'returned' => $requests->whereIn('status', [ProcurementRequest::STATUS_REJEITADO, ProcurementRequest::STATUS_DEVOLVIDO])->count(),
        ];

        return view('secretaria.dashboard', compact('requests', 'stats'));
    }
}
