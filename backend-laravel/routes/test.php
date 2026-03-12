<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

Route::get('/test-users', function () {
    $users = DB::table('users')->select('id', 'email', 'full_name', 'password_hash')->get();
    
    return response()->json([
        'total' => $users->count(),
        'users' => $users->map(function($user) {
            return [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'password_check' => Hash::check('admin123', $user->password_hash)
            ];
        })
    ]);
});

Route::post('/test-login', function () {
    $email = request('email');
    $password = request('password');
    
    $user = DB::table('users')->where('email', $email)->first();
    
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $passwordCheck = Hash::check($password, $user->password_hash);
    
    return response()->json([
        'user_found' => true,
        'email' => $user->email,
        'password_hash' => $user->password_hash,
        'password_check' => $passwordCheck,
        'status' => $user->status
    ]);
});

Route::post('/test-login-model', function () {
    try {
        $email = request('email');
        $password = request('password');
        
        $user = \App\Models\User::where('email', $email)->first();
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        $passwordCheck = Hash::check($password, $user->password_hash);
        
        if ($passwordCheck) {
            $token = $user->createToken('test-token')->plainTextToken;
            
            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'full_name' => $user->full_name
                ]
            ]);
        }
        
        return response()->json(['error' => 'Invalid password'], 401);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Exception occurred',
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
});