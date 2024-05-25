<?php

namespace App\Http\Controllers\Auth;

use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
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
            
            // GitHubのアクセストークンをログに出力して確認
            Log::info('GitHub access token', ['token' => $githubUser->token]);

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

            // ユーザーがログインしていることを確認するためのログ
            Log::info('User logged in', ['user' => $user, 'session' => session()->all()]);

            return redirect('/');
        } catch (\Exception $e) {
            Log::error('Authentication failed', ['error' => $e->getMessage()]);
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
        try {
            Log::info('Starting logout process');

            // Laravelからのログアウト処理
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
    
            Log::info('User logged out successfully');
    
            return redirect('/')->with('status', 'Logout successful');
        } catch (\Exception $e) {
            Log::error('Logout failed', ['error' => $e->getMessage()]);
            return redirect('/')->withErrors('Logout failed: ' . $e->getMessage());
        }
    }
}
