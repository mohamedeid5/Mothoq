<?php

namespace Tests\Feature;

use App\Actions\ServiceCenters\DeleteCenterImageAction;
use App\Actions\ServiceCenters\StoreCenterImageAction;
use App\Data\ServiceCenters\StoreCenterImageData;
use App\Models\CenterImage;
use App\Models\ServiceCenter;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class CenterImageStorageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_api_upload_uses_s3_and_returns_a_temporary_url(): void
    {
        config()->set('filesystems.center_images_disk', 's3');
        $storage = Storage::fake('s3');
        Storage::fake('public');
        $storage->buildTemporaryUrlsUsing(fn (string $path, DateTimeInterface $expiration): string => 'https://images.example.com/'.$path.'?expires='.$expiration->getTimestamp());
        $this->freezeTime();
        $owner = User::factory()->centerOwner()->create();
        $center = ServiceCenter::factory()->for($owner, 'owner')->create();

        $response = $this->withToken($owner->createToken('Test')->plainTextToken)
            ->postJson(route('api.v1.owner.service-centers.images.store', $center), [
                'image' => UploadedFile::fake()->image('workshop.jpg'),
            ]);

        $image = $center->images()->sole();
        $response->assertCreated()->assertJsonPath('data.url', 'https://images.example.com/'.$image->path.'?expires='.now()->addMinutes(15)->getTimestamp());
        $this->assertSame('s3', $image->disk);
        $this->assertSame('private', $storage->getVisibility($image->path));
        $storage->assertExists($image->path);
        Storage::disk('public')->assertEmpty();
    }

    public function test_web_upload_uses_s3_and_edit_page_displays_the_temporary_url(): void
    {
        $this->withoutVite();
        config()->set('filesystems.center_images_disk', 's3');
        $storage = Storage::fake('s3');
        $storage->buildTemporaryUrlsUsing(fn (): string => 'https://images.example.com/signed-image');
        $owner = User::factory()->centerOwner()->create();
        $center = ServiceCenter::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner)->post(route('owner.service-centers.images.store', $center), [
            'image' => UploadedFile::fake()->image('workshop.jpg'),
        ])->assertRedirect(route('owner.service-centers.edit', $center));

        $image = $center->images()->sole();
        $this->assertSame('s3', $image->disk);
        $storage->assertExists($image->path);
        $this->get(route('owner.service-centers.edit', $center))->assertSee('https://images.example.com/signed-image');
    }

    public function test_deleting_an_s3_image_uses_its_saved_disk_even_after_configuration_changes(): void
    {
        Storage::fake('s3');
        Storage::fake('public');
        config()->set('filesystems.center_images_disk', 'public');
        $image = CenterImage::factory()->create(['disk' => 's3']);
        Storage::disk('s3')->put($image->path, 's3 image');
        Storage::disk('public')->put($image->path, 'unrelated local image');

        app(DeleteCenterImageAction::class)->handle($image);

        $this->assertModelMissing($image);
        Storage::disk('s3')->assertMissing($image->path);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_legacy_images_keep_their_public_url_after_switching_uploads_to_s3(): void
    {
        config()->set('filesystems.center_images_disk', 's3');
        $image = CenterImage::factory()->create(['path' => 'service-centers/1/old.jpg']);

        $this->assertSame('public', $image->fresh()->disk);
        $this->assertSame(config('filesystems.disks.public.url').'/service-centers/1/old.jpg', $image->url());
    }

    public function test_rejected_eleventh_image_is_cleaned_up_from_s3(): void
    {
        config()->set('filesystems.center_images_disk', 's3');
        $storage = Storage::fake('s3');
        $center = ServiceCenter::factory()->create();
        CenterImage::factory()->count(10)->for($center)->create();

        try {
            app(StoreCenterImageAction::class)->handle($center, new StoreCenterImageData(
                UploadedFile::fake()->image('extra.jpg'), null, false,
            ));
            $this->fail('The image limit should reject the upload.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('image', $exception->errors());
        }

        $storage->assertEmpty();
        $this->assertDatabaseCount('center_images', 10);
    }

    public function test_failed_storage_deletion_keeps_the_database_record(): void
    {
        $image = CenterImage::factory()->create(['disk' => 's3']);
        Storage::shouldReceive('disk')->once()->with('s3')->andReturnSelf();
        Storage::shouldReceive('delete')->once()->with($image->path)->andReturn(false);

        try {
            app(DeleteCenterImageAction::class)->handle($image);
            $this->fail('A failed deletion should not be reported as successful.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The service center image could not be deleted.', $exception->getMessage());
        }

        $this->assertModelExists($image);
    }

    public function test_public_web_and_api_pages_use_temporary_s3_image_urls(): void
    {
        $this->withoutVite();
        Storage::fake('s3')->buildTemporaryUrlsUsing(fn (): string => 'https://images.example.com/signed-cover');
        $center = ServiceCenter::factory()->published()->create();
        CenterImage::factory()->cover()->for($center)->create(['disk' => 's3']);

        $this->get(route('service-centers.show', $center->slug))
            ->assertSee('https://images.example.com/signed-cover');
        $this->getJson(route('api.v1.service-centers.show', $center->slug))
            ->assertOk()
            ->assertJsonPath('data.cover_image.url', 'https://images.example.com/signed-cover')
            ->assertJsonPath('data.images.0.url', 'https://images.example.com/signed-cover');
    }

    public function test_failed_upload_does_not_create_a_database_record(): void
    {
        config()->set('filesystems.center_images_disk', 's3');
        $center = ServiceCenter::factory()->create();
        Storage::shouldReceive('disk')->once()->with('s3')->andReturnSelf();
        Storage::shouldReceive('putFileAs')->once()->andReturn(false);

        try {
            app(StoreCenterImageAction::class)->handle($center, new StoreCenterImageData(
                UploadedFile::fake()->image('failed.jpg'), null, false,
            ));
            $this->fail('A failed upload should not be reported as successful.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The service center image could not be stored.', $exception->getMessage());
        }

        $this->assertDatabaseCount('center_images', 0);
    }
}
