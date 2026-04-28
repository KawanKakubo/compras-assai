<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function dashboard()
    {
        $usersCount = User::count();
        $secretariasCount = User::where('role', User::ROLE_SECRETARIA)->count();
        $recentUsers = User::latest()->take(5)->get();
        
        return view('admin.dashboard', compact('usersCount', 'secretariasCount', 'recentUsers'));
    }

    public function indexUsers()
    {
        $users = User::all();
        return view('admin.users.index', compact('users'));
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,secretaria,gabinete,compras',
            'secretaria_name' => 'nullable|string|max:255',
            'secretaria_acronym' => 'nullable|string|max:20',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'secretaria_name' => $data['secretaria_name'],
            'secretaria_acronym' => $data['secretaria_acronym'],
        ]);

        return redirect()->back()->with('success', 'Usuário criado com sucesso!');
    }

    public function editUser(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:admin,secretaria,gabinete,compras',
            'secretaria_name' => 'nullable|string|max:255',
            'secretaria_acronym' => 'nullable|string|max:20',
        ]);

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'secretaria_name' => $data['secretaria_name'],
            'secretaria_acronym' => $data['secretaria_acronym'],
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
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
