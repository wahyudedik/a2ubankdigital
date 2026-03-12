<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\User\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $user = Auth::user()->load('customerProfile');

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil pengguna tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new ProfileResource($user)
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        $user->update($request->only(['full_name', 'phone_number']));
        
        if ($user->customerProfile) {
            $user->customerProfile->update($request->only([
                'address_domicile',
                'occupation'
            ]));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'data' => new ProfileResource($user->fresh('customerProfile'))
        ]);
    }

    public function updatePicture(Request $request): JsonResponse
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $user = Auth::user();

        try {
            // Delete old picture if exists
            if ($user->profile_picture_path) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $user->profile_picture_path));
            }

            // Upload new picture
            $path = $request->file('profile_picture')->storeAs(
                'profile_pictures',
                $user->id . '_' . time() . '.' . $request->file('profile_picture')->extension(),
                'public'
            );

            $user->update([
                'profile_picture_path' => '/storage/' . $path
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Foto profil berhasil diperbarui.',
                'data' => ['profile_picture_path' => $user->profile_picture_path]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupload foto: ' . $e->getMessage()
            ], 500);
        }
    }
}
