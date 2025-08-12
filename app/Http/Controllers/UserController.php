<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all()->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        });

        return Inertia::render('Admin/Users', [
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:users,username|unique:users,name',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed'],
            'role' => 'required|in:admin,officer,cashier',
        ]);

        User::create([
            'name' => $request->username,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect()->back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'username' => 'required|string|max:255|unique:users,username,' . $id . '|unique:users,name,' . $id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => $request->password ? ['confirmed'] : '',
            'role' => 'required|in:admin,officer,cashier',
        ]);

        $user->update([
            'name' => $request->username,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
        ]);

        if ($request->password) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent admin from deleting themselves
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Prevent admin from deactivating themselves
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot change your own account status.');
        }

        $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $user->update([
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'User status updated successfully.');
    }
} 