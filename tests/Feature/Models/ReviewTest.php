<?php

namespace Tests\Feature\Models;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_exposes_its_customer_center_and_moderation_state(): void
    {
        $user = User::factory()->create();
        $serviceCenter = ServiceCenter::factory()->published()->create();
        $review = Review::factory()
            ->for($user)
            ->for($serviceCenter)
            ->published()
            ->create(['rating' => 5]);

        $this->assertTrue($review->user->is($user));
        $this->assertTrue($review->serviceCenter->is($serviceCenter));
        $this->assertSame(5, $review->rating);
        $this->assertSame(ReviewStatus::Published, $review->status);
        $this->assertInstanceOf(\DateTimeInterface::class, $review->published_at);
    }

    public function test_customer_cannot_create_multiple_reviews_for_the_same_center(): void
    {
        $user = User::factory()->create();
        $serviceCenter = ServiceCenter::factory()->create();
        Review::factory()->for($user)->for($serviceCenter)->create();

        $this->expectException(QueryException::class);

        Review::factory()->for($user)->for($serviceCenter)->create();
    }

    public function test_published_scope_excludes_unpublished_reviews(): void
    {
        Review::factory()->create();
        $publishedReview = Review::factory()->published()->create();

        $reviews = Review::query()->published()->get();

        $this->assertCount(1, $reviews);
        $this->assertTrue($reviews->first()->is($publishedReview));
    }

    public function test_soft_deleted_customer_remains_available_to_review_history(): void
    {
        $customer = User::factory()->create();
        $review = Review::factory()->for($customer)->create();

        $customer->delete();
        $review->refresh();

        $this->assertSoftDeleted($customer);
        $this->assertTrue($review->user->is($customer));
    }
}
