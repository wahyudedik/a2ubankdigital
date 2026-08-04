<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\User\ProfileResource;
use App\Services\NotificationService;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    protected $notificationService;
    protected $logService;

    public function __construct(NotificationService $notificationService, LogService $logService)
    {
        $this->notificationService = $notificationService;
        $this->logService = $logService;
    }

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

    /**
     * Update dokumen KYC (KTP & Selfie) oleh nasabah.
     * Saat nasabah upload ulang, status KYC berubah menjadi PENDING untuk review admin.
     */
    public function updateKycDocuments(Request $request): JsonResponse
    {
        $request->validate([
            'ktp_image' => 'required_without:selfie_image|image|mimes:jpg,jpeg,png|max:2048',
            'selfie_image' => 'required_without:ktp_image|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'ktp_image.required_without' => 'Minimal satu dokumen (KTP atau Selfie) harus diupload.',
            'selfie_image.required_without' => 'Minimal satu dokumen (KTP atau Selfie) harus diupload.',
            'ktp_image.max' => 'Ukuran foto KTP maksimal 2MB.',
            'selfie_image.max' => 'Ukuran foto selfie maksimal 2MB.',
        ]);

        $user = Auth::user();
        $profile = $user->customerProfile;

        if (!$profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil nasabah tidak ditemukan.'
            ], 404);
        }

        // Validasi dimensi gambar
        if ($request->hasFile('ktp_image')) {
            $ktpTempPath = $request->file('ktp_image')->getRealPath();
            $imageInfo = @getimagesize($ktpTempPath);
            if ($imageInfo === false) {
                return response()->json(['status' => 'error', 'message' => 'File KTP bukan gambar yang valid atau corrupt.'], 422);
            }
            [$width, $height] = $imageInfo;
            if ($width < 600 || $height < 400) {
                return response()->json(['status' => 'error', 'message' => "Dimensi KTP terlalu kecil. Minimal 600x400 px, saat ini {$width}x{$height} px."], 422);
            }
        }

        if ($request->hasFile('selfie_image')) {
            $selfieTempPath = $request->file('selfie_image')->getRealPath();
            $imageInfo = @getimagesize($selfieTempPath);
            if ($imageInfo === false) {
                return response()->json(['status' => 'error', 'message' => 'File Selfie bukan gambar yang valid atau corrupt.'], 422);
            }
            [$width, $height] = $imageInfo;
            if ($width < 400 || $height < 400) {
                return response()->json(['status' => 'error', 'message' => "Dimensi Selfie terlalu kecil. Minimal 400x400 px, saat ini {$width}x{$height} px."], 422);
            }
        }

        DB::beginTransaction();
        try {
            $nikSanitized = preg_replace('/[^a-zA-Z0-9]/', '', $profile->nik);

            // Upload KTP baru jika ada
            if ($request->hasFile('ktp_image')) {
                // Hapus foto lama
                if ($profile->ktp_image_path) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $profile->ktp_image_path));
                }
                $ktpPath = $request->file('ktp_image')->storeAs(
                    'documents',
                    $nikSanitized . '_ktp_image_' . time() . '.' . $request->file('ktp_image')->extension(),
                    'public'
                );
                $profile->ktp_image_path = '/storage/' . $ktpPath;
            }

            // Upload Selfie baru jika ada
            if ($request->hasFile('selfie_image')) {
                // Hapus foto lama
                if ($profile->selfie_image_path) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $profile->selfie_image_path));
                }
                $selfiePath = $request->file('selfie_image')->storeAs(
                    'documents',
                    $nikSanitized . '_selfie_image_' . time() . '.' . $request->file('selfie_image')->extension(),
                    'public'
                );
                $profile->selfie_image_path = '/storage/' . $selfiePath;
            }

            // Set status PENDING untuk review admin
            $profile->kyc_status = 'PENDING';
            $profile->kyc_notes = null;
            $profile->kyc_verified_at = null;
            $profile->kyc_verified_by = null;
            $profile->save();

            // Notifikasi ke admin
            $this->notificationService->notifyAdmins(
                'Dokumen KYC Perlu Review',
                "Nasabah {$user->full_name} telah mengupload ulang dokumen KYC. Silakan review."
            );

            // Log audit
            $this->logService->logAudit('KYC_DOCUMENTS_UPDATED', 'customer_profiles', $user->id, [], [
                'customer_name' => $user->full_name,
                'updated_by' => 'customer',
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen KYC berhasil diperbarui. Status verifikasi berubah menjadi PENDING, menunggu review admin.',
                'data' => [
                    'kyc_status' => 'PENDING',
                    'ktp_image_path' => $profile->ktp_image_path,
                    'selfie_image_path' => $profile->selfie_image_path,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui dokumen KYC: ' . $e->getMessage()
            ], 500);
        }
    }
}
