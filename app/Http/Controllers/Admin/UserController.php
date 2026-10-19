<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private function roles(): array
    {
        return array_keys(config('mcms.roles'));
    }

    public function index()
    {
        return view('admin.users.index', ['users' => User::orderBy('id')->get()]);
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User(['role' => 'author', 'active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:190|unique:users,email',
            'role' => ['required', Rule::in($this->roles())],
            'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()],
        ]);

        $user = new User;
        $user->name = strip_tags($data['name']);
        $user->email = mb_strtolower($data['email']);
        if (\App\Support\Members::ready()) {
            $user->username = \App\Support\Members::usernameFrom($user->name, fn ($u) => User::where('username', $u)->exists());
        }
        $user->password = $data['password'];
        $user->role = $data['role'];
        $user->active = true;
        $user->password_changed_at = now();
        $user->save();
        audit('user.create', $user->email, ['role' => $user->role]);

        return redirect()->route('admin.users.index')->with('status', __('Потребителят е създаден.'));
    }

    public function edit(User $user)
    {
        return view('admin.users.form', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['nullable', Rule::in($this->roles())],
            'password' => ['nullable', Password::min(12)->letters()->mixedCase()->numbers()],
            'active' => 'nullable|boolean',
            'unlock' => 'nullable|boolean',
        ]);

        $self = $user->id === $request->user()->id;
        $role = $self ? $user->role : ($data['role'] ?? $user->role);       // cannot demote yourself
        $active = $self ? true : ! empty($data['active']);  // cannot deactivate yourself

        if ($user->isAdmin() && ($role !== 'admin' || ! $active)
            && User::where('role', 'admin')->where('active', true)->where('id', '!=', $user->id)->doesntExist()) {
            return back()->withErrors(['role' => __('Трябва да остане поне един активен администратор.')]);
        }

        $user->name = strip_tags($data['name']);
        $user->email = mb_strtolower($data['email']);
        $user->role = $role;
        $user->active = $active;

        if (! empty($data['password'])) {
            $user->password = $data['password'];
            $user->password_changed_at = now();
        }
        if (! empty($data['unlock'])) {
            $user->failed_attempts = 0;
            $user->locked_until = null;
        }
        $user->save();
        audit('user.update', $user->email, ['role' => $user->role, 'active' => $user->active]);

        return redirect()->route('admin.users.index')->with('status', __('Потребителят е обновен.'));
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, __('Не можете да изтриете себе си.'));
        abort_if($user->isAdmin() && User::where('role', 'admin')->where('id', '!=', $user->id)->doesntExist(), 422, __('Последният администратор не може да се изтрие.'));

        audit('user.delete', $user->email);
        $user->delete();

        return back()->with('status', __('Потребителят е изтрит.'));
    }
}
