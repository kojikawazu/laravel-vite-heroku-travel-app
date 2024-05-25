<?php

namespace App\Http\Controllers\Auth;

use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class GitHubController extends Controller
{
    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('github')->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback()
    {
        try {
            $githubUser = Socialite::driver('github')->stateless()->user();
            
            $user = User::where('github_id', $githubUser->id)
                        ->orWhere('email', $githubUser->email)
                        ->first();

            if ($user) {
                if (is_null($user->github_id)) {
                    $user->github_id = $githubUser->id;
                    $user->save();
                }
                Auth::login($user, true);
            } else {
                $user = User::create([
                    'name' => $githubUser->name,
                    'email' => $githubUser->email,
                    'github_id' => $githubUser->id,
                    'avatar' => $githubUser->avatar,
                    'password' => bcrypt(Str::random(16)),
                ]);

                Auth::login($user, true);
            }

            return redirect('/');
        } catch (\Exception $e) {
            return redirect('/')->withErrors(['login' => 'Authentication failed.']);
        }
    }

    /**
     * Logout the user from both Laravel and Supabase.
     *
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $client = new Client();
        $supabaseLogoutUrl = env('SUPABASE_URL') . '/auth/v1/logout';
        
        try {
            $response = $client->post($supabaseLogoutUrl, [
                'headers' => [
                    'apikey' => env('SUPABASE_API_KEY'),
                    'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
                ],
            ]);

            // Laravelからのログアウト処理
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/');
        } catch (\Exception $e) {
            return redirect('/')->withErrors('Logout failed: ' . $e->getMessage());
        }
    }
}
