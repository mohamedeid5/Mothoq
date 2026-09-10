<?php

namespace Tests\Feature\Models;

use App\Models\Review;
use App\Models\ServiceCenter;
use App\Models\User;
use App\ReviewStatus;
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
}
