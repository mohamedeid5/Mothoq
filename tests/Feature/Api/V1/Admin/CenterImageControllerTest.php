<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\CenterImage;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CenterImageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_non_admin_returns_403(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);

        $this->actingWithToken($owner)
            ->postJson(route('api.v1.admin.service-centers.images.store', $serviceCenter), [
                'image' => UploadedFile::fake()->image('workshop.jpg'),
            ])
            ->assertForbidden();
    }

    public function test_admin_manages_images_for_any_service_center(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->create();

        $response = $this->actingWithToken($admin)
            ->postJson(route('api.v1.admin.service-centers.images.store', $serviceCenter), [
                'image' => UploadedFile::fake()->image('workshop.webp'),
                'alt_text' => 'صورة رفعها المدير',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.alt_text', 'صورة رفعها المدير')
            ->assertJsonPath('data.is_cover', true);

        $centerImage = CenterImage::query()->sole();

        $this->actingWithToken($admin)
            ->patchJson(route('api.v1.admin.service-centers.images.update', [$serviceCenter, $centerImage]), [
                'alt_text' => 'وصف محدث',
            ])
            ->assertOk()
            ->assertJsonPath('data.alt_text', 'وصف محدث');

        $this->actingWithToken($admin)
            ->deleteJson(route('api.v1.admin.service-centers.images.destroy', [$serviceCenter, $centerImage]))
            ->assertNoContent();

        $this->assertModelMissing($centerImage);
        Storage::disk('public')->assertMissing($centerImage->path);
    }

    private function actingWithToken(User $user): static
    {
        return $this->withToken($user->createToken('Test')->plainTextToken);
    }
}
