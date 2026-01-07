<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Autenticación de Usuarios
 *
 * Endpoints para gestionar el acceso, registro y perfil de los usuarios en la billetera.
 */
class AuthController extends Controller
{
    /**
     * Registro de Usuario
     * * Crea una nueva cuenta de usuario y asigna un saldo inicial.
     * * @bodyParam name string required El nombre completo del usuario. Example: Juan Perez
     * @bodyParam email string required Correo electrónico único. Example: juan@example.com
     * @bodyParam password string required Contraseña (mínimo 6 caracteres). Example: secret123
     * @bodyParam saldo number Saldo inicial con el que comienza la cuenta. Example: 1000.00
     * * @response 201 {
     * "id": 1,
     * "name": "Juan Perez",
     * "email": "juan@example.com",
     * "saldo": 1000
     * }
     */
    public function register(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email', // "unique:tabla,columna"
            'password' => 'required|min:6',
        ], [
            'email.unique' => __('messages.validation.email_taken'), // Mensaje personalizado
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'saldo' => $request->saldo, // saldo inicial

        ]);

        return response()->json($user, 201);
    }

    /**
     * Inicio de Sesión
     * * Autentica al usuario y devuelve un Token de Acceso Personal (Passport).
     * * @bodyParam email string required Correo del usuario. Example: juan@example.com
     * @bodyParam password string required Contraseña del usuario. Example: secret123
     * * @response 200 {
     * "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1...",
     * "user": {"id": 1, "name": "Juan Perez", "email": "juan@example.com"}
     * }
     * @response 401 {
     * "message": "Credenciales inválidas"
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => __('messages.auth.login_error')
            ], 401);
        }

        $user = Auth::user();

        $token = $user->createToken('login')->accessToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Actualizar Perfil
     * * Permite al usuario autenticado modificar sus datos personales.
     * * @authenticated
     * @bodyParam name string Nombre opcional. Example: Juan P.
     * @bodyParam email string Email opcional (debe ser único). Example: juan.p@example.com
     * @bodyParam password string Contraseña opcional (mínimo 6 caracteres).
     * @bodyParam password_confirmation string Confirmación obligatoria si se envía password.
     * * @response 200 {
     * "message": "Perfil actualizado",
     * "user": {"id": 1, "name": "Juan P.", "email": "juan.p@example.com"}
     * }
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
            ],
            'password' => 'sometimes|min:6|confirmed',
        ]);

        if ($request->has('name'))
            $user->name = $request->name;
        if ($request->has('email'))
            $user->email = $request->email;
        if ($request->has('password'))
            $user->password = bcrypt($request->password);

        $user->save();

        return response()->json([
            'message' => __('messages.auth.updated'),
            'user' => $user
        ]);
    }

    /**
     * Eliminar cuenta y revocar tokens (Passport)
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        // Passport: Revocamos todos los tokens del usuario antes de borrar
        $user->tokens->each(function ($token) {
            $token->revoke();
        });

        $user->delete();

        return response()->json([
            'message' => __('messages.auth.deleted')
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => __('messages.auth.logout')
        ]);
    }

}
