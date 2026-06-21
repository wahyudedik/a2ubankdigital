<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePictureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_profile_picture(): void
    {
        Storage::fake('public');

        $user = User::create([
            'bank_id'       => 'CIF-TEST-123456',
            'role_id'       => 9,
            'full_name'     => 'Test Customer',
            'email'         => 'customer@example.com',
            'phone_number'  => '081234567890',
            'password_hash' => bcrypt('password123'),
            'status'        => 'ACTIVE',
        ]);

        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        $response = $this->actingAs($user, 'web')->postJson('/ajax/user/profile/picture', [
            'profile_picture' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $user->refresh();
        $this->assertNotNull($user->profile_picture_path);
        
        // Assert storage has the file
        $cleanPath = str_replace('/storage/', '', $user->profile_picture_path);
        Storage::disk('public')->assertExists($cleanPath);
    }
}
