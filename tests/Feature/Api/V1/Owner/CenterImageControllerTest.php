<?php

namespace Tests\Feature\Api\V1\Owner;

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

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $serviceCenter = ServiceCenter::factory()->create();

        $this->postJson(route('api.v1.owner.service-centers.images.store', $serviceCenter), [
            'image' => UploadedFile::fake()->image('workshop.jpg'),
        ])->assertUnauthorized();
    }

    public function test_valid_image_creates_the_first_cover_and_returns_201(): void
    {
        Storage::fake('public');
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingWithToken($owner)
            ->postJson(route('api.v1.owner.service-centers.images.store', $serviceCenter), [
                'image' => UploadedFile::fake()->image('workshop.png', 1200, 800),
                'alt_text' => 'واجهة المركز',
                'is_cover' => false,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.alt_text', 'واجهة المركز')
            ->assertJsonPath('data.is_cover', true)
            ->assertJsonPath('data.sort_order', 0);

        $centerImage = $serviceCenter->images()->sole();

        Storage::disk('public')->assertExists($centerImage->path);
    }

    public function test_non_image_upload_returns_422_without_saving_a_file(): void
    {
        Storage::fake('public');
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);

        $this->actingWithToken($owner)
            ->postJson(route('api.v1.owner.service-centers.images.store', $serviceCenter), [
                'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        $this->assertDatabaseCount('center_images', 0);
        Storage::disk('public')->assertEmpty();
    }

    public function test_eleventh_image_returns_422_and_cleans_up_the_uploaded_file(): void
    {
        Storage::fake('public');
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);
        CenterImage::factory()->count(CenterImage::MAX_PER_SERVICE_CENTER)->for($serviceCenter)->create();

        $this->actingWithToken($owner)
            ->postJson(route('api.v1.owner.service-centers.images.store', $serviceCenter), [
                'image' => UploadedFile::fake()->image('extra.jpg'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        $this->assertDatabaseCount('center_images', CenterImage::MAX_PER_SERVICE_CENTER);
        Storage::disk('public')->assertEmpty();
    }

    public function test_owner_cannot_access_another_owners_center_images(): void
    {
        Storage::fake('public');
        $owner = User::factory()->centerOwner()->create();
        $otherCenter = ServiceCenter::factory()->create();

        $this->actingWithToken($owner)
            ->postJson(route('api.v1.owner.service-centers.images.store', $otherCenter), [
                'image' => UploadedFile::fake()->image('workshop.jpg'),
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('center_images', 0);
        Storage::disk('public')->assertEmpty();
    }

    public function test_image_from_another_center_returns_404(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);
        $otherImage = CenterImage::factory()->create();

        $this->actingWithToken($owner)
            ->patchJson(
                route('api.v1.owner.service-centers.images.update', [$serviceCenter, $otherImage]),
                ['alt_text' => 'وصف جديد'],
            )
            ->assertNotFound();

        $this->assertNotSame('وصف جديد', $otherImage->fresh()->alt_text);
    }

    public function test_owner_updates_description_and_sets_a_new_cover(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);
        $oldCover = CenterImage::factory()->cover()->for($serviceCenter)->create();
        $centerImage = CenterImage::factory()->for($serviceCenter)->create(['sort_order' => 1]);

        $this->actingWithToken($owner)
            ->patchJson(route('api.v1.owner.service-centers.images.update', [$serviceCenter, $centerImage]), [
                'alt_text' => 'منطقة الصيانة',
            ])
            ->assertOk()
            ->assertJsonPath('data.alt_text', 'منطقة الصيانة');

        $this->actingWithToken($owner)
            ->putJson(route('api.v1.owner.service-centers.images.cover.update', [$serviceCenter, $centerImage]))
            ->assertOk()
            ->assertJsonPath('data.is_cover', true);

        $this->assertFalse($oldCover->fresh()->is_cover);
        $this->assertTrue($centerImage->fresh()->is_cover);
    }

    public function test_owner_reorders_all_images_and_rejects_an_incomplete_list(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);
        $firstImage = CenterImage::factory()->for($serviceCenter)->create(['sort_order' => 0]);
        $secondImage = CenterImage::factory()->for($serviceCenter)->create(['sort_order' => 1]);

        $this->actingWithToken($owner)
            ->patchJson(route('api.v1.owner.service-centers.images.reorder', $serviceCenter), [
                'images' => [
                    ['id' => $firstImage->id, 'sort_order' => 1],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('images');

        $response = $this->actingWithToken($owner)
            ->patchJson(route('api.v1.owner.service-centers.images.reorder', $serviceCenter), [
                'images' => [
                    ['id' => $firstImage->id, 'sort_order' => 1],
                    ['id' => $secondImage->id, 'sort_order' => 0],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $secondImage->id)
            ->assertJsonPath('data.0.sort_order', 0)
            ->assertJsonPath('data.1.id', $firstImage->id)
            ->assertJsonPath('data.1.sort_order', 1);
    }

    public function test_owner_deletes_an_image_and_its_stored_file(): void
    {
        Storage::fake('public');
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);
        $centerImage = CenterImage::factory()->for($serviceCenter)->create([
            'path' => 'service-centers/1/delete.jpg',
        ]);
        Storage::disk('public')->put($centerImage->path, 'image');

        $this->actingWithToken($owner)
            ->deleteJson(route('api.v1.owner.service-centers.images.destroy', [$serviceCenter, $centerImage]))
            ->assertNoContent();

        $this->assertModelMissing($centerImage);
        Storage::disk('public')->assertMissing($centerImage->path);
    }

    private function actingWithToken(User $user): static
    {
        return $this->withToken($user->createToken('Test')->plainTextToken);
    }
}
