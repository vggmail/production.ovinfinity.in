<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'FullName' => 'required|string|max:100',
            'ContactNo' => 'required|string|max:20',
            'EmailId' => 'required|email|max:100',
            'Address' => 'nullable|string|max:255',
            'City' => 'required|string|max:50',
            'Password' => 'nullable|string|min:6|confirmed',
        ]);

        $user->FullName = $validated['FullName'];
        $user->ContactNo = $validated['ContactNo'];
        $user->EmailId = $validated['EmailId'];
        $user->Address = $validated['Address'];
        $user->City = $validated['City'];

        if (!empty($validated['Password'])) {
            $user->Password = Hash::make($validated['Password']);
        }

        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully.');
    }
}
