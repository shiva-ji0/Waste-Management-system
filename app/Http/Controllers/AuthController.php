<?php
namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
       public function register(Request $request)
    {

        $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed', Password::defaults()],
        ]);

        // ✅ Create a new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // ✅ Generate Sanctum token for API login
        $token = $user->createToken('API Token')->plainTextToken;

        // ✅ Return JSON response
        return response()->json([
            'status' => true,
            'message' => 'User registered successfully.',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ], 201);
    }
    public function login(AuthRequest $request)
    {

        if (!Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
        ])) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid login credentials.'
            ], 401);
        }

        $user = Auth::user();

        return response()->json([
            'status' => true,
            'message' => 'Authenticated successfully.',
            'data' => [
                'token' => $user->createToken('API Token')->plainTextToken,
                'user' => $user,
            ]
        ], 200);
    }
public function logout(Request $request)
{

    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'status' => true,
        'message' => 'Logged out successfully.'
    ], 200);
}
}

