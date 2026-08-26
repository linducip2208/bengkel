<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    // ===== USER MANAGEMENT =====
    public function userIndex(Request $request)
    {
        $query = User::with('roles');
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('email', 'like', "%{$request->search}%"));
        }
        $users = $query->latest()->paginate(20)->withQueryString();
        $roles = Role::orderBy('name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function userStore(Request $request)
    {

        $this->authorize('users.manage');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => 'required|min:6',
            'role' => 'required|exists:roles,name',
            'is_active' => 'nullable|boolean',
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active', true),
        ]);
        $user->assignRole($validated['role']);
        ActivityLog::record('user.create', $user, "Create user {$user->email} with role {$validated['role']}");

        return back()->with('success', "User {$user->name} dibuat.");
    }

    public function userUpdate(Request $request, User $user)
    {

        $this->authorize('users.manage');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|min:6',
            'role' => 'required|exists:roles,name',
            'is_active' => 'nullable|boolean',
        ]);
        $data = ['name' => $validated['name'], 'email' => $validated['email'], 'is_active' => $request->boolean('is_active', true)];
        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }
        $user->update($data);
        $user->syncRoles([$validated['role']]);
        ActivityLog::record('user.update', $user, "Update user {$user->email} role={$validated['role']}");

        return back()->with('success', 'User diperbarui.');
    }

    public function userDestroy(User $user)
    {

        $this->authorize('users.manage');
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa hapus diri sendiri.');
        }
        if ($user->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
            return back()->with('error', 'Minimal harus ada 1 super_admin.');
        }
        ActivityLog::record('user.delete', $user, "Delete user {$user->email}");
        $user->delete();

        return back()->with('success', 'User dihapus.');
    }

    // ===== ROLE MANAGEMENT =====
    public function roleIndex()
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get()->groupBy(fn ($p) => explode('.', $p->name)[0]);

        return view('users.roles', compact('roles', 'permissions'));
    }

    public function roleStore(Request $request)
    {

        $this->authorize('roles.manage');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('roles', 'name')],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);
        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }
        ActivityLog::record('role.create', $role, "Create role {$role->name}");

        return back()->with('success', "Role {$role->name} dibuat.");
    }

    public function roleUpdate(Request $request, Role $role)
    {

        $this->authorize('roles.manage');
        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);
        $role->syncPermissions($validated['permissions'] ?? []);
        ActivityLog::record('role.update', $role, "Update permissions for role {$role->name}");

        return back()->with('success', "Permission untuk role {$role->name} diperbarui.");
    }

    public function roleDestroy(Role $role)
    {

        $this->authorize('roles.manage');
        if (in_array($role->name, ['super_admin', 'admin'])) {
            return back()->with('error', 'Role sistem tidak bisa dihapus.');
        }
        if ($role->users()->count() > 0) {
            return back()->with('error', "Role {$role->name} masih dipakai user, tidak bisa dihapus.");
        }
        ActivityLog::record('role.delete', $role, "Delete role {$role->name}");
        $role->delete();

        return back()->with('success', 'Role dihapus.');
    }

    public function apiTokens()
    {
        return view('users.api-tokens');
    }

    public function createToken(Request $request)
    {

        $this->authorize('users.manage');
        $request->validate(['name' => 'required|string|max:255']);
        $token = auth()->user()->createToken($request->name);

        return back()->with('plain_text_token', $token->plainTextToken)->with('success', 'Token berhasil dibuat.');
    }

    public function revokeToken($tokenId)
    {

        $this->authorize('users.manage');
        $token = auth()->user()->tokens()->findOrFail($tokenId);
        $token->delete();

        return back()->with('success', 'Token direvoke.');
    }
}
