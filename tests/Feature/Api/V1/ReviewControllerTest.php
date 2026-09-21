<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_store_returns_401(): void
    {
        $serviceCenter = ServiceCenter::factory()->published()->create();

        $this->postJson(route('api.v1.reviews.store', $serviceCenter->slug), [
            'rating' => 5,
        ])->assertUnauthorized();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_customer_creates_pending_review_and_receives_201(): void
    {
        $customer = User::factory()->create();
        $serviceCenter = ServiceCenter::factory()->published()->create();

        $this->actingWithToken($customer)
            ->postJson(route('api.v1.reviews.store', $serviceCenter->slug), [
                'rating' => 4,
                'comment' => 'تجربة جيدة',
            ])
            ->assertCreated()
            ->assertJsonPath('data.rating', 4)
            ->assertJsonPath('data.comment', 'تجربة جيدة')
            ->assertJsonPath('data.status', ReviewStatus::Pending->value)
            ->assertJsonPath('data.service_center.id', $serviceCenter->id);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $customer->id,
            'service_center_id' => $serviceCenter->id,
            'status' => ReviewStatus::Pending->value,
        ]);
    }

    public function test_store_returns_422_for_invalid_rating(): void
    {
        $customer = User::factory()->create();
        $serviceCenter = ServiceCenter::factory()->published()->create();

        $this->actingWithToken($customer)
            ->postJson(route('api.v1.reviews.store', $serviceCenter->slug), [
                'rating' => 6,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating'])
            ->assertJsonPath('errors.rating.0', 'التقييم يجب أن يكون من نجمة إلى خمس نجوم.');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_center_owner_cannot_create_review(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->published()->create();

        $this->actingWithToken($owner)
            ->postJson(route('api.v1.reviews.store', $serviceCenter->slug), [
                'rating' => 5,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_customer_updates_their_review_and_it_returns_to_pending(): void
    {
        $customer = User::factory()->create();
        $review = Review::factory()->published()->for($customer)->create();

        $this->actingWithToken($customer)
            ->patchJson(route('api.v1.reviews.update', $review), [
                'rating' => 2,
                'comment' => 'تجربة معدلة',
            ])
            ->assertOk()
            ->assertJsonPath('data.rating', 2)
            ->assertJsonPath('data.status', ReviewStatus::Pending->value)
            ->assertJsonPath('data.published_at', null);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 2,
            'status' => ReviewStatus::Pending->value,
            'published_at' => null,
        ]);
    }

    public function test_customer_cannot_update_another_customers_review_and_receives_404(): void
    {
        $customer = User::factory()->create();
        $otherReview = Review::factory()->create();

        $this->actingWithToken($customer)
            ->patchJson(route('api.v1.reviews.update', $otherReview), [
                'rating' => 1,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('reviews', [
            'id' => $otherReview->id,
            'rating' => $otherReview->rating,
        ]);
    }

    public function test_customer_deletes_their_review_and_receives_204(): void
    {
        $customer = User::factory()->create();
        $review = Review::factory()->for($customer)->create();

        $this->actingWithToken($customer)
            ->deleteJson(route('api.v1.reviews.destroy', $review))
            ->assertNoContent();

        $this->assertSoftDeleted($review);
    }

    private function actingWithToken(User $user): static
    {
        return $this->withToken($user->createToken('Test')->plainTextToken);
    }
}
