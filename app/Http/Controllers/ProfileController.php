<?php
/**
 * LTPC Enrollment Management System (LTPC_EMS)
 *
 * @copyright  2025-2026 Clarence Buenaflor & Jester Pastor
 * @author     Clarence Buenaflor <cbuenaflor2@ssct.edu.ph>
 * @author     Jester Pastor <pastorjester98@mail.com>
 * @license    Proprietary - All Rights Reserved
 *
 * Unauthorized copying, modification, or distribution of this
 * software is strictly prohibited without express written permission.
 */
namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        Auth::user()->fill($request->validated());

        if (Auth::user()->isDirty('email')) {
            Auth::user()->email_verified_at = null;
        }

        Auth::user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     * DISABLED: Account deletion is admin-managed only.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Self-deletion is disabled in this admin-managed system.
        // Only administrators can delete user accounts via the admin panel.
        abort(403, 'Account deletion is not permitted. Please contact an administrator.');
    }
}
