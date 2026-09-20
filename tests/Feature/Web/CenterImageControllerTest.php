<?php

namespace Tests\Feature\Web;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_uploads_the_first_image_as_the_cover_from_the_edit_page(): void
    {
        Storage::fake('public');
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('owner.service-centers.edit', $serviceCenter))
            ->assertOk()
            ->assertSee('صور مركز الصيانة')
            ->assertSee(route('owner.service-centers.images.store', $serviceCenter));

        $this->actingAs($owner)
            ->post(route('owner.service-centers.images.store', $serviceCenter), [
                'image' => UploadedFile::fake()->image('workshop.jpg', 1200, 800),
                'alt_text' => 'واجهة المركز',
                'is_cover' => false,
            ])
            ->assertRedirect(route('owner.service-centers.edit', $serviceCenter))
            ->assertSessionHas('success', 'تمت إضافة الصورة بنجاح.');

        $centerImage = $serviceCenter->images()->sole();

        $this->assertTrue($centerImage->is_cover);
        $this->assertSame(0, $centerImage->sort_order);
        $this->assertSame('واجهة المركز', $centerImage->alt_text);
        Storage::disk('public')->assertExists($centerImage->path);
    }

    public function test_owner_updates_an_image_description_and_sets_the_cover(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);
        $oldCover = CenterImage::factory()->cover()->for($serviceCenter)->create();
        $centerImage = CenterImage::factory()->for($serviceCenter)->create(['sort_order' => 1]);

        $this->actingAs($owner)
            ->patch(route('owner.service-centers.images.update', [$serviceCenter, $centerImage]), [
                'alt_text' => 'منطقة استقبال العملاء',
            ])
            ->assertRedirect(route('owner.service-centers.edit', $serviceCenter));

        $this->assertSame('منطقة استقبال العملاء', $centerImage->fresh()->alt_text);

        $this->actingAs($owner)
            ->put(route('owner.service-centers.images.cover.update', [$serviceCenter, $centerImage]))
            ->assertRedirect(route('owner.service-centers.edit', $serviceCenter))
            ->assertSessionHas('success', 'تم تعيين صورة الغلاف بنجاح.');

        $this->assertFalse($oldCover->fresh()->is_cover);
        $this->assertTrue($centerImage->fresh()->is_cover);
    }

    public function test_owner_reorders_all_center_images(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);
        $firstImage = CenterImage::factory()->for($serviceCenter)->create(['sort_order' => 0]);
        $secondImage = CenterImage::factory()->for($serviceCenter)->create(['sort_order' => 1]);

        $this->actingAs($owner)
            ->patch(route('owner.service-centers.images.reorder', $serviceCenter), [
                'images' => [
                    ['id' => $firstImage->id, 'sort_order' => 1],
                    ['id' => $secondImage->id, 'sort_order' => 0],
                ],
            ])
            ->assertRedirect(route('owner.service-centers.edit', $serviceCenter))
            ->assertSessionHas('success', 'تم حفظ ترتيب الصور بنجاح.');

        $this->assertSame(1, $firstImage->fresh()->sort_order);
        $this->assertSame(0, $secondImage->fresh()->sort_order);
    }

    public function test_deleting_the_cover_removes_the_file_and_promotes_the_next_image(): void
    {
        Storage::fake('public');
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->create(['owner_id' => $owner->id]);
        $coverImage = CenterImage::factory()->cover()->for($serviceCenter)->create([
            'path' => 'service-centers/1/cover.jpg',
        ]);
        $nextImage = CenterImage::factory()->for($serviceCenter)->create(['sort_order' => 1]);
        Storage::disk('public')->put($coverImage->path, 'image');

        $this->actingAs($owner)
            ->delete(route('owner.service-centers.images.destroy', [$serviceCenter, $coverImage]))
            ->assertRedirect(route('owner.service-centers.edit', $serviceCenter))
            ->assertSessionHas('success', 'تم حذف الصورة بنجاح.');

        $this->assertModelMissing($coverImage);
        $this->assertTrue($nextImage->fresh()->is_cover);
        Storage::disk('public')->assertMissing($coverImage->path);
    }

    public function test_owner_cannot_manage_another_owners_images(): void
    {
        Storage::fake('public');
        $owner = User::factory()->centerOwner()->create();
        $otherCenter = ServiceCenter::factory()->create();

        $this->actingAs($owner)
            ->post(route('owner.service-centers.images.store', $otherCenter), [
                'image' => UploadedFile::fake()->image('workshop.jpg'),
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('center_images', 0);
        Storage::disk('public')->assertEmpty();
    }

    public function test_admin_uploads_an_image_for_any_service_center(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $serviceCenter = ServiceCenter::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.service-centers.images.store', $serviceCenter), [
                'image' => UploadedFile::fake()->image('admin-upload.webp'),
                'is_cover' => true,
            ])
            ->assertRedirect(route('admin.service-centers.edit', $serviceCenter))
            ->assertSessionHas('success', 'تمت إضافة الصورة بنجاح.');

        $centerImage = $serviceCenter->images()->sole();

        $this->assertTrue($centerImage->is_cover);
        Storage::disk('public')->assertExists($centerImage->path);
    }

    public function test_public_center_page_displays_the_saved_image_gallery(): void
    {
        $serviceCenter = ServiceCenter::factory()->published()->create();
        CenterImage::factory()->cover()->for($serviceCenter)->create([
            'path' => 'service-centers/1/cover.jpg',
            'alt_text' => 'واجهة المركز',
        ]);
        CenterImage::factory()->for($serviceCenter)->create([
            'path' => 'service-centers/1/workshop.jpg',
            'alt_text' => 'منطقة الصيانة',
            'sort_order' => 1,
        ]);

        $this->get(route('service-centers.show', $serviceCenter->slug))
            ->assertOk()
            ->assertSee('صور مركز الصيانة')
            ->assertSee('/storage/service-centers/1/cover.jpg', false)
            ->assertSee('/storage/service-centers/1/workshop.jpg', false)
            ->assertSee('واجهة المركز')
            ->assertSee('منطقة الصيانة');
    }
}
