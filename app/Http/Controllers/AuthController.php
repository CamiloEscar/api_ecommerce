<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordMail;
use App\Mail\VerifiedMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', [
            'except' => [
                'login',
                'register',
                'login_ecommerce',
                'verified_auth',
                'verified_email',
                'verified_code',
                'new_password',
                'redirect',
                'callback',
                'facebookLogin'
            ]
        ]);
    }

    /* -----------------------------
     | LOGIN ADMIN
     |-----------------------------*/
    public function login(Request $request)
    {
        if (!$token = auth('api')->attempt([
            'email' => $request->email,
            'password' => $request->password,
            'type_user' => 1
        ])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /* -----------------------------
     | LOGIN ECOMMERCE
     |-----------------------------*/
    public function login_ecommerce(Request $request)
    {
        if (!$token = auth('api')->attempt([
            'email' => $request->email,
            'password' => $request->password,
            'type_user' => 2
        ])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!auth('api')->user()->email_verified_at) {
            return response()->json(['error' => 'Email no verificado'], 401);
        }

        return $this->respondWithToken($token);
    }

    /* -----------------------------
     | REGISTRO
     |-----------------------------*/
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'surname' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'type_user' => 2,
            'uniqd' => uniqid()
        ]);

        try {
            Mail::to($user->email)->send(new VerifiedMail($user));
        } catch (\Exception $e) {
            Log::warning('Error enviando email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'user' => $user
        ], 201);
    }

    /* -----------------------------
     | PERFIL
     |-----------------------------*/
    public function me()
    {
        $user = auth('api')->user();

        return response()->json([
            'name' => $user->name,
            'surname' => $user->surname,
            'phone' => $user->phone,
            'email' => $user->email,
            'bio' => $user->bio,
            'sexo' => $user->sexo,
            'address_city' => $user->address_city,
            'avatar' => $user->avatar
                ? asset('storage/' . $user->avatar)
                : 'https://cdn-icons-png.flaticon.com/512/12449/12449018.png'
        ]);
    }

    /* -----------------------------
     | UPDATE PERFIL
     |-----------------------------*/
    public function update(Request $request)
    {
        $user = auth('api')->user();

        if ($request->password) {
            $user->update([
                'password' => bcrypt($request->password)
            ]);
            return response()->json(['message' => 'Password actualizado']);
        }

        if ($request->hasFile('file_imagen')) {
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }
            $path = Storage::putFile('users', $request->file('file_imagen'));
            $request->merge(['avatar' => $path]);
        }

        $user->update($request->except(['password', 'file_imagen']));

        return response()->json(['message' => 'Perfil actualizado']);
    }

    /* -----------------------------
     | LOGOUT
     |-----------------------------*/
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Logout exitoso']);
    }

    /* -----------------------------
     | TOKEN
     |-----------------------------*/
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => auth('api')->user()
        ]);
    }

    /* -----------------------------
     | FACEBOOK LOGIN
     |-----------------------------*/

     public function facebookLogin(Request $request)
{
    $request->validate([
        'access_token' => 'required|string'
    ]);

    try {
        $fbUser = Socialite::driver('facebook')
            ->stateless()
            ->userFromToken($request->access_token);

        // ⚠️ Facebook puede NO devolver email
        $email = $fbUser->getEmail();

        if (!$email) {
            $email = 'fb_' . $fbUser->getId() . '@facebook.local';
        }

        $user = User::where('facebook_id', $fbUser->getId())
            ->orWhere('email', $email)
            ->first();

        if (!$user) {
            $user = User::create([
                'name'              => $fbUser->getName() ?? 'Usuario Facebook',
                'email'             => $email,
                'facebook_id'       => $fbUser->getId(),
                'email_verified_at' => $fbUser->getEmail() ? now() : null,
                'type_user'         => 2,
                'password'          => bcrypt(\Illuminate\Support\Str::random(32)),
            ]);
        } else {
            if (!$user->facebook_id) {
                $user->update(['facebook_id' => $fbUser->getId()]);
            }
        }

        $token = auth('api')->login($user);

        return response()->json([
            'access_token' => $token,
            'needs_email'  => str_ends_with($email, '@facebook.local'),
            'user'         => $user
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error autenticando con Facebook',
            'details' => $e->getMessage()
        ], 422);
    }

    }

   public function completeEmail(Request $request) {
  $request->validate([
    'email' => 'required|email|unique:users,email'
  ]);

  $user = auth()->user();
  $user->email = $request->email;
  $user->email_verified_at = now();
  $user->save();

  return response()->json([
    'message' => 'Email actualizado',
    'user' => $user
  ]);
}

public function googleLogin(Request $request)
{
    $request->validate([
        'access_token' => 'required|string'
    ]);

    try {
        $googleUser = Socialite::driver('google')
            ->stateless()
            ->userFromToken($request->access_token);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Token de Google inválido'
        ], 401);
    }

    // 🚨 Edge case: Google sin email
    if (!$googleUser->getEmail()) {
        return response()->json([
            'needs_email' => true,
            'google_id' => $googleUser->getId(),
            'name' => $googleUser->getName()
        ]);
    }

    $user = User::where('google_id', $googleUser->getId())
        ->orWhere('email', $googleUser->getEmail())
        ->first();

    if (!$user) {
        $user = User::create([
            'name' => $googleUser->getName() ?? 'Usuario Google',
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'email_verified_at' => now(),
            'type_user' => 2,
            'password' => bcrypt(Str::random(16))
        ]);
    } else {
        if (!$user->google_id) {
            $user->update([
                'google_id' => $googleUser->getId()
            ]);
        }
    }

    $token = auth('api')->login($user);

    return response()->json([
        'access_token' => $token,
        'user' => $user
    ]);
}







   }
