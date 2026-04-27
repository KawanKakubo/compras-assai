<?php

namespace App\Http\Controllers\Compras;

use App\Http\Controllers\Controller;
use App\Models\Planning\ProcurementRequest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $requests = ProcurementRequest::with('user')
            ->where('status', ProcurementRequest::STATUS_APROVADO_GABINETE)
            ->latest()
            ->get();

        $stats = [
            'pending_compras' => $requests->count(),
            'finalized' => ProcurementRequest::where('status', ProcurementRequest::STATUS_FINALIZADO)->count(),
        ];

        return view('compras.dashboard', compact('requests', 'stats'));
    }

    public function finalize($id)
    {
        $procurementRequest = ProcurementRequest::findOrFail($id);
        $procurementRequest->status = ProcurementRequest::STATUS_FINALIZADO;
        $procurementRequest->save();

        return redirect()->back()->with('success', 'Processo de compra finalizado!');
    }
}
