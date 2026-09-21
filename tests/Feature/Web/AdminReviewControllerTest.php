<?php

namespace Tests\Feature\Web;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminReviewControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_lists_and_filters_reviews(): void
    {
        $admin = User::factory()->admin()->create();
        $pendingReview = Review::factory()->create(['comment' => 'مراجعة منتظرة']);
        $publishedReview = Review::factory()->published()->create(['comment' => 'مراجعة منشورة']);

        $this->actingAs($admin)
            ->get(route('admin.reviews.index', ['status' => ReviewStatus::Pending->value]))
            ->assertOk()
            ->assertSee('مراجعة منتظرة')
            ->assertDontSee('مراجعة منشورة')
            ->assertSee(route('admin.reviews.status.update', $pendingReview));

        $this->assertModelExists($publishedReview);
    }

    public function test_non_admin_cannot_open_review_moderation(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.reviews.index'))
            ->assertForbidden();
    }

    public function test_admin_publishes_pending_review(): void
    {
        $admin = User::factory()->admin()->create();
        $review = Review::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.reviews.status.update', $review), [
                'status' => ReviewStatus::Published->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'تم تحديث حالة التقييم بنجاح.');

        $review->refresh();
        $this->assertSame(ReviewStatus::Published, $review->status);
        $this->assertNotNull($review->published_at);
    }

    public function test_admin_rejects_published_review_and_clears_publish_date(): void
    {
        $admin = User::factory()->admin()->create();
        $review = Review::factory()->published()->create();

        $this->actingAs($admin)
            ->patch(route('admin.reviews.status.update', $review), [
                'status' => ReviewStatus::Rejected->value,
            ])
            ->assertRedirect();

        $review->refresh();
        $this->assertSame(ReviewStatus::Rejected, $review->status);
        $this->assertNull($review->published_at);
    }
}
