<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AccountSettingsController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboard.settings.index', [
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $emailChanged = $user->email !== $data['email'];

        $user->name = $data['name'];
        $user->email = $data['email'];

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('success', 'Account profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $user->password = $data['password'];
        $user->save();

        return back()->with('success', 'Password updated successfully.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $data['avatar']->store('avatars', 'public');
        $user->avatar_path = $path;
        $user->save();

        return back()->with('success', 'Profile photo updated successfully.');
    }

    public function removeAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }

        return back()->with('success', 'Profile photo removed successfully.');
    }

    public function updateProfessionalProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'position'    => ['nullable', 'string', 'max:120'],
            'bio'         => ['nullable', 'string', 'max:1000'],
            'tech_stack'  => ['nullable', 'string'],
            'github_url'  => ['nullable', 'url', 'max:255'],
            'linkedin_url'=> ['nullable', 'url', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            // experience entries
            'experience'                => ['nullable', 'array', 'max:20'],
            'experience.*.role'         => ['required_with:experience', 'string', 'max:120'],
            'experience.*.company'      => ['required_with:experience', 'string', 'max:120'],
            'experience.*.period'       => ['nullable', 'string', 'max:60'],
            'experience.*.description'  => ['nullable', 'string', 'max:500'],
            // education entries
            'education'                 => ['nullable', 'array', 'max:20'],
            'education.*.institution'   => ['required_with:education', 'string', 'max:120'],
            'education.*.degree'        => ['required_with:education', 'string', 'max:120'],
            'education.*.period'        => ['nullable', 'string', 'max:60'],
        ]);

        // Parse tech_stack from comma-separated string → array
        $techRaw = trim($data['tech_stack'] ?? '');
        $techStack = $techRaw !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $techRaw)), fn($t) => $t !== ''))
            : [];

        $user = $request->user();
        $user->position     = $data['position'] ?? null;
        $user->bio          = $data['bio'] ?? null;
        $user->tech_stack   = $techStack ?: null;
        $user->github_url   = $data['github_url'] ?? null;
        $user->linkedin_url = $data['linkedin_url'] ?? null;
        $user->website_url  = $data['website_url'] ?? null;
        $user->twitter_url  = $data['twitter_url'] ?? null;
        $user->instagram_url = $data['instagram_url'] ?? null;
        $user->facebook_url = $data['facebook_url'] ?? null;

        // Filter out empty experience / education entries
        $experience = collect($data['experience'] ?? [])->filter(fn($e) => !empty($e['role']) && !empty($e['company']))->values()->toArray();
        $education  = collect($data['education'] ?? [])->filter(fn($e) => !empty($e['institution']) && !empty($e['degree']))->values()->toArray();

        $user->experience = $experience ?: null;
        $user->education  = $education ?: null;
        $user->save();

        return back()->with('success', 'Professional profile updated successfully.');
    }

    public function destroyAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'confirmation_text' => ['required', 'in:DELETE'],
        ]);

        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return redirect('/')->with('success', 'Your account has been deleted.');
    }
}
