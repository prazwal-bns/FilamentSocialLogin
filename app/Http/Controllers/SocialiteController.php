<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller{

public function redirect(string $provider)
{
    return Socialite::driver($provider)->stateless()->redirect();
}

public function callback(string $provider)
{
    $response = Socialite::driver($provider)->stateless()->user();
    // dd($response);
        $user = User::firstWhere('email', $response->getEmail());
        if ($user) {
            // $user->update([$provider . '_id' => $response->getId()]);
            auth()->login($user, true);
        } else {
            $user = User::create([
                $provider . '_id' => $response->getId(),
                'name' => $response->getName(),
                'email' => $response->getEmail(),
                'avatar' => $response->getAvatar(),
                'password' => 'password',
            ]);
        }
        Auth::login($user);
        return redirect()->intended(url('/admin'));
    }
}