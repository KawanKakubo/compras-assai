<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Secretaria;
use App\Models\User;
use Illuminate\Http\Request;

class SecretariaController extends Controller
{
    public function index()
    {
        $secretarias = Secretaria::with(['users' => function($q) {
            $q->select('id', 'name', 'role', 'secretaria_id');
        }])->get();

        return view('admin.secretarias.index', compact('secretarias'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'acronym' => 'required|string|max:20|unique:secretarias,acronym',
        ]);

        $data['acronym'] = strtoupper($data['acronym']);

        Secretaria::create($data);

        return redirect()->back()->with('success', 'Secretaria cadastrada com sucesso!');
    }

    public function edit(Secretaria $secretaria)
    {
        return view('admin.secretarias.edit', compact('secretaria'));
    }

    public function update(Request $request, Secretaria $secretaria)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'acronym' => 'required|string|max:20|unique:secretarias,acronym,' . $secretaria->id,
        ]);

        $data['acronym'] = strtoupper($data['acronym']);

        $secretaria->update($data);

        return redirect()->route('admin.secretarias.index')->with('success', 'Secretaria atualizada com sucesso!');
    }

    public function destroy(Secretaria $secretaria)
    {
        // Disassociate users from this secretariat
        User::where('secretaria_id', $secretaria->id)->update([
            'secretaria_id' => null
        ]);

        $secretaria->delete();

        return redirect()->back()->with('success', 'Secretaria excluída com sucesso!');
    }
}
