<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    /**
     * Show forced password change form for first login users.
     */
    public function showForceChange(Request $request): View
    {
        return view('auth.force-password', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Handle forced password change.
     */
    public function forceChange(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => $validated['password'],
            'must_change_password' => false,
            'last_password_changed_at' => now(),
        ]);

        if ($request->user()?->role === 'user') {
            return redirect()->route('tasks.evaluator.index')->with('success', 'تم تحديث كلمة المرور بنجاح.');
        }

        return redirect()->route('dashboard')->with('success', 'تم تحديث كلمة المرور بنجاح.');
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => $validated['password'],
            'last_password_changed_at' => now(),
        ]);

        return back()->with('status', 'password-updated');
    }
}
