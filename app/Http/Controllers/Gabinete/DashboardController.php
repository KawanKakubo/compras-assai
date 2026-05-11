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
            $query->where('status', ProcurementRequest::STATUS_EM_ANALISE);
        } else {
            $query->where('status', $request->status);
        }

        // Filter by Secretaria (User)
        if ($request->has('secretaria_id') && $request->secretaria_id != '') {
            $query->where('user_id', $request->secretaria_id);
        }

        $requests = $query->latest()->get();
        $secretarias = User::whereIn('role', [User::ROLE_ELABORADOR, User::ROLE_SECRETARIO])->get();

        $stats = [
            'pending' => ProcurementRequest::where('status', ProcurementRequest::STATUS_EM_ANALISE)->count(),
            'approved' => ProcurementRequest::where('status', ProcurementRequest::STATUS_APROVADO_COMPRAS)->count(),
            'denied' => ProcurementRequest::where('status', ProcurementRequest::STATUS_REJEITADO)->count(),
        ];

        return view('gabinete.dashboard', compact('requests', 'secretarias', 'stats'));
    }

    public function approve($id)
    {
        $procurementRequest = ProcurementRequest::findOrFail($id);
        
        // Redirect to the show page so they can review and sign the document
        return redirect()->route('planning.module-one.show', $procurementRequest)
            ->with('info', 'Por favor, revise os detalhes e clique em "Aprovar e Assinar" para concluir a aprovação desta demanda.');
    }

    public function deny(Request $request, $id)
    {
        $procurementRequest = ProcurementRequest::findOrFail($id);
        $procurementRequest->status = ProcurementRequest::STATUS_REJEITADO;
        
        // Save justification in metadata if provided
        if ($request->has('justification')) {
            $metadata = $procurementRequest->metadata ?? [];
            $metadata['rejection_justification'] = $request->justification;
            $procurementRequest->metadata = $metadata;
        }

        $procurementRequest->save();

        return redirect()->back()->with('success', 'Solicitação rejeitada com sucesso.');
    }
}
