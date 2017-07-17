<?php

namespace LBudget\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use LBudget\User;
use Socialite;

class LoginController extends Controller
{
    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return Response
     */
    public function handleProviderCallback()
    {
        $googleUser = Socialite::driver('google')->user();
		$user = User::where('email', $googleUser->email)->first();
		if (!$user) {
			//register...
			$user = new User();
			$user->name = $googleUser->name;
			$user->email = $googleUser->email;
			$user->init_done = false;
			$user->password = 'none';
			$user->save();
		}
        Auth::login($user);
		return redirect()->intended('home');
	}

	public function logout() {
		Auth::logout();
		return redirect()->intended('home');
	}
}
