<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Secretaria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function dashboard()
    {
        $usersCount = User::count();
        $secretariasCount = Secretaria::count();
        $recentUsers = User::latest()->take(5)->get();
        
        return view('admin.dashboard', compact('usersCount', 'secretariasCount', 'recentUsers'));
    }

    public function indexUsers()
    {
        $users = User::with('secretaria')->get();
        $secretarias = Secretaria::all();
        return view('admin.users.index', compact('users', 'secretarias'));
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,elaborador,secretario,gabinete,compras',
            'secretaria_id' => 'nullable|exists:secretarias,id',
            'cpf' => 'nullable|string|max:14',
            'libresign_username' => 'nullable|string|max:255',
            'libresign_signer_account' => 'nullable|string|email|max:255',
            'libresign_password' => 'nullable|string',
        ]);

        // Enforce: one secretary per secretariat
        if ($data['role'] === User::ROLE_SECRETARIO && !empty($data['secretaria_id'])) {
            $exists = User::where('role', User::ROLE_SECRETARIO)
                ->where('secretaria_id', $data['secretaria_id'])
                ->exists();
            if ($exists) {
                return redirect()->back()->withErrors(['secretaria_id' => 'Esta secretaria já possui um secretário cadastrado!'])->withInput();
            }
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'secretaria_id' => $data['secretaria_id'] ?? null,
            'cpf' => $data['cpf'] ?? null,
            'libresign_username' => $data['libresign_username'] ?? null,
            'libresign_signer_account' => $data['libresign_signer_account'] ?? null,
            'libresign_password' => !empty($data['libresign_password']) ? \Illuminate\Support\Facades\Crypt::encryptString($data['libresign_password']) : null,
        ]);

        return redirect()->back()->with('success', 'Usuário criado com sucesso!');
    }

    public function editUser(User $user)
    {
        $secretarias = Secretaria::all();
        return view('admin.users.edit', compact('user', 'secretarias'));
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:admin,elaborador,secretario,gabinete,compras',
            'secretaria_id' => 'nullable|exists:secretarias,id',
            'cpf' => 'nullable|string|max:14',
            'libresign_username' => 'nullable|string|max:255',
            'libresign_signer_account' => 'nullable|string|email|max:255',
            'libresign_password' => 'nullable|string',
        ]);

        // Enforce: one secretary per secretariat
        if ($data['role'] === User::ROLE_SECRETARIO && !empty($data['secretaria_id'])) {
            $exists = User::where('role', User::ROLE_SECRETARIO)
                ->where('secretaria_id', $data['secretaria_id'])
                ->where('id', '!=', $user->id)
                ->exists();
            if ($exists) {
                return redirect()->back()->withErrors(['secretaria_id' => 'Esta secretaria já possui um secretário cadastrado!'])->withInput();
            }
        }

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'secretaria_id' => $data['secretaria_id'] ?? null,
            'cpf' => $data['cpf'] ?? null,
            'libresign_username' => $data['libresign_username'] ?? null,
            'libresign_signer_account' => $data['libresign_signer_account'] ?? null,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        if ($request->filled('libresign_password')) {
            $updateData['libresign_password'] = \Illuminate\Support\Facades\Crypt::encryptString($data['libresign_password']);
        }

        $user->update($updateData);

        return redirect()->route('admin.users.index')->with('success', 'Usuário atualizado com sucesso!');
    }

    public function destroyUser(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'Você não pode excluir a si mesmo!');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Usuário excluído com sucesso!');
    }
}
