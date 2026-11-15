<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();

        return Inertia::render('Master/Users', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'nullable|email|max:255|unique:users,email',
            'role' => ['required', Rule::in(['admin', 'kasir', 'customer'])],
            'password' => 'required|string|min:4',
            'is_active' => 'sometimes|boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('user_photos', 'public');
            $validated['photo_path'] = $path;
        }

        unset($validated['photo']);

        $validated['password'] = Hash::make($validated['password']);

        if (! isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        if (! empty($validated['email'])) {
            $validated['email_verified_at'] = now();
        }

        User::create($validated);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        // Debugging
        // \Log::info('Update User Data:', $request->all());
        // \Log::info('Has File:', ['has_file' => $request->hasFile('photo')]);

        $photo = $request->file('photo');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'kasir', 'customer'])],
            'is_active' => 'sometimes|boolean',
            'password' => 'nullable|string|min:4',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($user->photo_path && Storage::disk('public')->exists($user->photo_path)) {
                Storage::disk('public')->delete($user->photo_path);
            }
            $path = $request->file('photo')->store('user_photos', 'public');
            $validated['photo_path'] = $path;
        }

        unset($validated['photo']);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        if (isset($validated['is_active']) && ! $validated['is_active'] && $user->id === auth()->id()) {
            return back()->withErrors(['message' => 'Tidak bisa menonaktifkan akun sendiri.']);
        }

        if (! empty($validated['email']) && $validated['email'] !== $user->email) {
            $validated['email_verified_at'] = now();
        }

        $user->update($validated);

        return back()->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $user)
    {
        // Don't allow deleting yourself
        if ($user->id === auth()->id()) {
            return back()->withErrors(['message' => 'Tidak bisa menghapus akun sendiri.']);
        }

        if ($user->photo_path && Storage::disk('public')->exists($user->photo_path)) {
            Storage::disk('public')->delete($user->photo_path);
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }
}
