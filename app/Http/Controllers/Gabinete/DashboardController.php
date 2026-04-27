<?php

namespace App\Http\Controllers\Gabinete;

use App\Http\Controllers\Controller;
use App\Models\Planning\ProcurementRequest;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $query = ProcurementRequest::with('user');

        // Filter by status if needed, but default is pending
        if (!$request->has('status')) {
            $query->where('status', ProcurementRequest::STATUS_AGUARDANDO_GABINETE);
        } else {
            $query->where('status', $request->status);
        }

        // Filter by Secretaria (User)
        if ($request->has('secretaria_id') && $request->secretaria_id != '') {
            $query->where('user_id', $request->secretaria_id);
        }

        $requests = $query->latest()->get();
        $secretarias = User::where('role', User::ROLE_SECRETARIA)->get();

        $stats = [
            'pending' => ProcurementRequest::where('status', ProcurementRequest::STATUS_AGUARDANDO_GABINETE)->count(),
            'approved' => ProcurementRequest::where('status', ProcurementRequest::STATUS_APROVADO_GABINETE)->count(),
            'denied' => ProcurementRequest::where('status', ProcurementRequest::STATUS_NEGADO_GABINETE)->count(),
        ];

        return view('gabinete.dashboard', compact('requests', 'secretarias', 'stats'));
    }

    public function approve($id)
    {
        $procurementRequest = ProcurementRequest::findOrFail($id);
        $procurementRequest->status = ProcurementRequest::STATUS_APROVADO_GABINETE;
        $procurementRequest->save();

        return redirect()->back()->with('success', 'Solicitação aprovada e encaminhada ao Compras!');
    }

    public function deny($id)
    {
        $procurementRequest = ProcurementRequest::findOrFail($id);
        $procurementRequest->status = ProcurementRequest::STATUS_NEGADO_GABINETE;
        $procurementRequest->save();

        return redirect()->back()->with('success', 'Solicitação negada com sucesso.');
    }
}
