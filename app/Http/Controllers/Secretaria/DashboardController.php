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
        $requests = ProcurementRequest::where('user_id', $user->id)
            ->latest()
            ->get();

        $stats = [
            'total' => $requests->count(),
            'pending' => $requests->where('status', ProcurementRequest::STATUS_AGUARDANDO_GABINETE)->count(),
            'approved' => $requests->where('status', ProcurementRequest::STATUS_APROVADO_GABINETE)->count(),
            'denied' => $requests->where('status', ProcurementRequest::STATUS_NEGADO_GABINETE)->count(),
        ];

        return view('secretaria.dashboard', compact('requests', 'stats'));
    }
}
