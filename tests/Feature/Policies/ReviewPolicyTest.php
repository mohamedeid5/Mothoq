<?php

namespace Tests\Feature\Policies;

use App\Models\Review;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ReviewPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_and_moderate_all_reviews(): void
    {
        $admin = User::factory()->admin()->create();
        $review = Review::factory()->create();
        $gate = Gate::forUser($admin);

        $this->assertTrue($gate->allows('viewAny', Review::class));
        $this->assertTrue($gate->allows('view', $review));
        $this->assertTrue($gate->allows('moderate', $review));
    }

    public function test_customer_can_create_for_published_center_and_manage_only_their_review(): void
    {
        $customer = User::factory()->create();
        $publishedCenter = ServiceCenter::factory()->published()->create();
        $draftCenter = ServiceCenter::factory()->create();
        $ownReview = Review::factory()->for($customer)->for($publishedCenter)->create();
        $otherReview = Review::factory()->create();
        $gate = Gate::forUser($customer);

        $this->assertTrue($gate->allows('create', [Review::class, $publishedCenter]));
        $this->assertFalse($gate->allows('create', [Review::class, $draftCenter]));
        $this->assertTrue($gate->allows('update', $ownReview));
        $this->assertTrue($gate->allows('delete', $ownReview));
        $this->assertFalse($gate->allows('update', $otherReview));
        $this->assertFalse($gate->allows('delete', $otherReview));
        $this->assertFalse($gate->allows('moderate', $ownReview));
    }

    public function test_center_owner_cannot_create_or_manage_reviews(): void
    {
        $owner = User::factory()->centerOwner()->create();
        $serviceCenter = ServiceCenter::factory()->published()->for($owner, 'owner')->create();
        $review = Review::factory()->for($serviceCenter)->create();
        $gate = Gate::forUser($owner);

        $this->assertFalse($gate->allows('create', [Review::class, $serviceCenter]));
        $this->assertFalse($gate->allows('update', $review));
        $this->assertFalse($gate->allows('delete', $review));
        $this->assertFalse($gate->allows('moderate', $review));
    }
}
