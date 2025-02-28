<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SocialiteController extends Controller{

    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    // public function callback(string $provider)
    // {
    //     $response = Socialite::driver($provider)->stateless()->user();
    //     // dd($response);
    //         $user = User::firstWhere('email', $response->getEmail());
    //         if ($user) {
    //             // $user->update([$provider . '_id' => $response->getId()]);
    //             auth()->login($user, true);
    //         } else {
    //             $user = User::create([
    //                 $provider . '_id' => $response->getId(),
    //                 'name' => $response->getName(),
    //                 'email' => $response->getEmail(),
    //                 'avatar' => $response->getAvatar(),
    //                 'password' => 'password',
    //             ]);
    //         }
    //         Auth::login($user);
    //         return redirect()->intended(url('/admin'));
    // }

    public function callback(string $provider)
    {
        $response = Socialite::driver($provider)->stateless()->user();

        $user = User::firstWhere('email', $response->getEmail());

        // Download and store avatar locally
        $avatarPath = null;
        if ($response->getAvatar()) {
            $avatarPath = $this->downloadAndStoreAvatar($response->getAvatar());
        }

        if ($user) {
            // Update avatar if we have a new one
            if ($avatarPath) {
                $user->update(['avatar' => $avatarPath]);
            }
            auth()->login($user, true);
        } else {
            $user = User::create([
                $provider . '_id' => $response->getId(),
                'name' => $response->getName(),
                'email' => $response->getEmail(),
                'avatar' => $avatarPath,
                'password' => 'password',
            ]);
        }

        Auth::login($user);
        return redirect()->intended(url('/admin'));
    }

    /**
     * Download external avatar and store it locally
     *
     * @param string $url
     * @return string|null
     */
    protected function downloadAndStoreAvatar(string $url)
    {
        try {
            // Get image content
            $imageContents = file_get_contents($url);

            if ($imageContents === false) {
                return null;
            }

            // Generate a unique filename
            $filename = 'avatar-' . Str::random(20) . '.jpg';
            $path = 'avatars/' . $filename;

            // Store the file
            Storage::disk('public')->put($path, $imageContents);

            return $path;
        } catch (\Exception $e) {
            // Log the error if needed
            // \Log::error('Failed to download avatar: ' . $e->getMessage());
            return null;
        }
    }
}
