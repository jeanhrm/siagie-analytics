<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Institution;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isAdmin()) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index()
    {
        $users        = User::with('institution')->where('role', '!=', 'admin')->latest()->get();
        $institutions = Institution::withCount('uploads')->latest()->get();

        return view('admin.index', compact('users', 'institutions'));
    }

    public function createUser(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users',
            'password'       => 'required|min:8',
            'role'           => 'required|in:director,docente',
            'institution_id' => 'required|exists:institutions,id',
        ]);

        User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'role'           => $request->role,
            'institution_id' => $request->institution_id,
        ]);

        return redirect()->route('admin.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        if ($user->isAdmin()) abort(403);
        $user->delete();

        return redirect()->route('admin.index')
            ->with('success', 'Usuario eliminado.');
    }

    public function createInstitution(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'code'     => 'required|string|unique:institutions',
            'ugel'     => 'required|string',
            'district' => 'required|string',
            'level'    => 'required|in:primaria,secundaria',
            'context'  => 'required|in:rural,urbana',
        ]);

        Institution::create([
            'name'           => $request->name,
            'code'           => $request->code,
            'ugel'           => $request->ugel,
            'district'       => $request->district,
            'province'       => $request->province ?? 'Huancavelica',
            'region'         => 'Huancavelica',
            'level'          => $request->level,
            'director_name'  => $request->director_name,
        ]);

        return redirect()->route('admin.index')
            ->with('success', 'Institución creada correctamente.');
    }
}