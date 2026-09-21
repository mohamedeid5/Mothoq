<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_non_admin_index_returns_403(): void
    {
        $customer = User::factory()->create();

        $this->actingWithToken($customer)
            ->getJson(route('api.v1.admin.reviews.index'))
            ->assertForbidden();
    }

    public function test_admin_lists_reviews_filtered_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $pendingReview = Review::factory()->create();
        Review::factory()->published()->create();

        $this->actingWithToken($admin)
            ->getJson(route('api.v1.admin.reviews.index', [
                'status' => ReviewStatus::Pending->value,
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendingReview->id)
            ->assertJsonPath('data.0.status', ReviewStatus::Pending->value);
    }

    public function test_admin_publishes_review(): void
    {
        $admin = User::factory()->admin()->create();
        $review = Review::factory()->create();

        $this->actingWithToken($admin)
            ->patchJson(route('api.v1.admin.reviews.status.update', $review), [
                'status' => ReviewStatus::Published->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', ReviewStatus::Published->value)
            ->assertJsonPath('data.status_label', ReviewStatus::Published->label());

        $review->refresh();
        $this->assertSame(ReviewStatus::Published, $review->status);
        $this->assertNotNull($review->published_at);
    }

    public function test_admin_status_update_returns_422_for_pending_status(): void
    {
        $admin = User::factory()->admin()->create();
        $review = Review::factory()->published()->create();

        $this->actingWithToken($admin)
            ->patchJson(route('api.v1.admin.reviews.status.update', $review), [
                'status' => ReviewStatus::Pending->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status'])
            ->assertJsonPath('errors.status.0', 'حالة التقييم المختارة غير صالحة.');

        $review->refresh();
        $this->assertSame(ReviewStatus::Published, $review->status);
    }

    private function actingWithToken(User $user): static
    {
        return $this->withToken($user->createToken('Test')->plainTextToken);
    }
}
