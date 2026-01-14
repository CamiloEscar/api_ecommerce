<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordMail;
use App\Mail\VerifiedMail;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str; // Importante para Str::random()
use Laravel\Socialite\Facades\Socialite;

/**
 * @OA\Tag(
 * name="Autenticación",
 * description="Operaciones relacionadas al login y autenticación de usuarios"
 * )
 */
/**
 * @OA\SecurityScheme(
 * securityScheme="bearerAuth",
 * type="http",
 * scheme="bearer",
 * bearerFormat="JWT"
 * )
*/

class AuthController extends Controller
{
    public function __construct()
    {
        // Definimos qué rutas no necesitan token JWT para ser accedidas
        $this->middleware('auth:api', ['except' => [
            'login', 'register', 'login_ecommerce', 'verified_auth', 
            'verified_email', 'verified_code', 'new_password', 
            'login_google'
        ]]);
    }

    /**
     * Registro de Usuario Manual
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'surname' => 'required',
            'phone' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'phone' => $request->phone,
            'email' => $request->email,
            'type_user' => 2, // Cliente ecommerce
            'uniqd' => uniqid(),
            'password' => bcrypt($request->password), 
        ]);

        try {
            Mail::to($user->email)->send(new VerifiedMail($user));
        } catch (\Exception $e) {
            Log::info("No se pudo enviar el correo: " . $e->getMessage());
        }

        return response()->json($user, 201);
    }

    /**
     * Login para Administradores (type_user 1)
     */
    public function login()
    {
        if (! $token = auth('api')->attempt([
            'email' => request()->email,
            'password' => request()->password,
            'type_user' => 1
        ])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Login para Ecommerce (Clientes type_user 2)
     */
    public function login_ecommerce()
    {
        if (! $token = auth('api')->attempt([
            'email' => request()->email,
            'password' => request()->password,
            'type_user' => 2
        ])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Opcional: Validar si el email ha sido verificado
        /*
        if (!auth('api')->user()->email_verified_at) {
            return response()->json(['error' => 'Unauthorized', 'message' => 'Email no verificado'], 401);
        }
        */

        return $this->respondWithToken($token);
    }

    /**
     * Login / Registro con Google (Única definición)
     */
    public function login_google(Request $request) 
    {
        try {
            // Validamos el token recibido desde Angular
            $user_google = Socialite::driver('google')->userFromToken($request->access_token);
            $user = User::where('email', $user_google->getEmail())->first();

            if (!$user) {
                // Si no existe, creamos el usuario
                $user = User::create([
                    'name' => $user_google->offsetGet('given_name') ?? $user_google->getName(),
                    'surname' => $user_google->offsetGet('family_name') ?? '',
                    'email' => $user_google->getEmail(),
                    'password' => bcrypt(Str::random(16)), // Contraseña aleatoria segura
                    'phone' => '00000000',
                    'type_user' => 2,
                    'uniqd' => uniqid(),
                    'email_verified_at' => now(), // Verificado automático para Google
                ]);
            }

            $token = auth('api')->login($user);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'user' => [
                    'full_name' => $user->name . ' ' . $user->surname,
                    'email' => $user->email,
                    'id' => $user->id
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error en Google: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener datos del usuario autenticado
     */
    public function me()
    {
        $user = auth('api')->user();
        return response()->json([
            'name' => $user->name,
            'surname' => $user->surname,
            'phone' => $user->phone,
            'email' => $user->email,
            'avatar' => $user->avatar ? env("APP_URL") . "storage/" . $user->avatar : 'https://cdn-icons-png.flaticon.com/512/12449/12449018.png',
        ]);
    }

    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => [
                'full_name' => auth('api')->user()->name . ' ' . auth('api')->user()->surname,
                'email' => auth('api')->user()->email,
            ]
        ]);
    }

    // --- MÉTODOS DE VERIFICACIÓN Y PERFIL ---

    public function update(Request $request){
        $user = User::find(auth('api')->user()->id);
        
        if($request->password){
            $user->update(["password" => bcrypt($request->password)]);
            return response()->json(["message" => 200]);
        }

        $user->update($request->all());
        return response()->json(["message" => 200]);
    }

    public function verified_email(Request $request) {
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->update(["code_verified" => uniqid()]);
            Mail::to($user->email)->send(new ForgotPasswordMail($user));
            return response()->json(["message" => "Código enviado", "status" => 200]);
        }
        return response()->json(["message" => "No encontrado", "status" => 403], 403);
    }

    public function verified_code(Request $request) {
        $user = User::where('code_verified', $request->code)->first();
        return $user ? response()->json(["message" => 200]) : response()->json(['message' => 403], 403);
    }

    public function new_password(Request $request) {
        $user = User::where('code_verified', $request->code)->first();
        $user->update(['password' => bcrypt($request->new_password), 'code_verified' => null]);
        return response()->json(["message" => 200]);
    }
}