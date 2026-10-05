<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    /**
     * Daftar semua banner (untuk halaman admin).
     */
    public function index(): JsonResponse
    {
        $banners = Banner::orderBy('sort_order')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $banners,
        ]);
    }

    /**
     * Upload multiple gambar banner sekaligus.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'required|image|mimes:png,jpg,jpeg|max:1024',
        ]);

        try {
            $nextSortOrder = (int) Banner::max('sort_order') + 1;
            $created = [];

            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('banners', 'public');

                $created[] = Banner::create([
                    'title' => $request->input("titles.{$index}"),
                    'image_path' => $path,
                    'link_url' => $request->input("link_urls.{$index}"),
                    'is_active' => true,
                    'sort_order' => $nextSortOrder + $index,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => count($created) . ' banner berhasil diunggah.',
                'data' => $created,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengunggah banner: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle status aktif banner (Tampilkan / Sembunyikan).
     */
    public function toggle($id): JsonResponse
    {
        $banner = Banner::findOrFail($id);
        $banner->is_active = !$banner->is_active;
        $banner->save();

        return response()->json([
            'status' => 'success',
            'message' => $banner->is_active
                ? 'Banner berhasil ditampilkan.'
                : 'Banner berhasil disembunyikan.',
            'data' => $banner,
        ]);
    }

    /**
     * Hapus banner beserta file gambarnya.
     */
    public function destroy($id): JsonResponse
    {
        $banner = Banner::findOrFail($id);

        if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
            Storage::disk('public')->delete($banner->image_path);
        }

        $banner->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Banner berhasil dihapus.',
        ]);
    }
}
