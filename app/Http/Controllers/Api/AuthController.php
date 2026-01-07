<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'saldo' => $request->saldo, // saldo inicial

        ]);

        return response()->json($user, 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Credenciales inválidas'
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
     * Actualizar perfil (Passport)
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
            'message' => 'Perfil actualizado',
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
            'message' => 'Cuenta eliminada y tokens revocados'
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

}
