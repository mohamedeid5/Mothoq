<?php

namespace Tests\Feature\Web;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_customer_creates_pending_review_from_public_center_page(): void
    {
        $customer = User::factory()->create();
        $serviceCenter = ServiceCenter::factory()->published()->create();

        $this->actingAs($customer)
            ->get(route('service-centers.show', $serviceCenter->slug))
            ->assertOk()
            ->assertSee('قيّم تجربتك')
            ->assertSee(route('reviews.store', $serviceCenter->slug));

        $this->actingAs($customer)
            ->post(route('reviews.store', $serviceCenter->slug), [
                'rating' => 5,
                'comment' => 'خدمة ممتازة وسريعة.',
            ])
            ->assertRedirect(route('service-centers.show', $serviceCenter->slug))
            ->assertSessionHas('success', 'تم إرسال تقييمك للمراجعة بنجاح.');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $customer->id,
            'service_center_id' => $serviceCenter->id,
            'rating' => 5,
            'comment' => 'خدمة ممتازة وسريعة.',
            'status' => ReviewStatus::Pending->value,
            'published_at' => null,
        ]);
    }

    public function test_duplicate_review_returns_validation_error_without_creating_another_record(): void
    {
        $customer = User::factory()->create();
        $serviceCenter = ServiceCenter::factory()->published()->create();
        Review::factory()->for($customer)->for($serviceCenter)->create();

        $this->actingAs($customer)
            ->from(route('service-centers.show', $serviceCenter->slug))
            ->post(route('reviews.store', $serviceCenter->slug), [
                'rating' => 4,
                'comment' => 'محاولة أخرى',
            ])
            ->assertRedirect(route('service-centers.show', $serviceCenter->slug))
            ->assertSessionHasErrors(['rating' => 'لقد أضفت تقييمًا لهذا المركز من قبل.']);

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_customer_update_returns_published_review_to_pending(): void
    {
        $customer = User::factory()->create();
        $review = Review::factory()->published()->for($customer)->create();

        $this->actingAs($customer)
            ->patch(route('reviews.update', $review), [
                'rating' => 3,
                'comment' => 'تم تعديل تجربتي.',
            ])
            ->assertRedirect(route('service-centers.show', $review->serviceCenter->slug))
            ->assertSessionHas('success', 'تم تحديث تقييمك وإرساله للمراجعة.');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => 'تم تعديل تجربتي.',
            'status' => ReviewStatus::Pending->value,
            'published_at' => null,
        ]);
    }

    public function test_customer_cannot_update_another_customers_review(): void
    {
        $customer = User::factory()->create();
        $otherReview = Review::factory()->create();

        $this->actingAs($customer)
            ->patch(route('reviews.update', $otherReview), [
                'rating' => 1,
                'comment' => 'غير مسموح',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('reviews', [
            'id' => $otherReview->id,
            'rating' => $otherReview->rating,
        ]);
    }

    public function test_customer_deletes_their_review(): void
    {
        $customer = User::factory()->create();
        $review = Review::factory()->for($customer)->create();

        $this->actingAs($customer)
            ->delete(route('reviews.destroy', $review))
            ->assertRedirect(route('service-centers.show', $review->serviceCenter->slug))
            ->assertSessionHas('success', 'تم حذف تقييمك بنجاح.');

        $this->assertSoftDeleted($review);
    }

    public function test_customer_can_submit_a_new_review_after_deleting_the_previous_one(): void
    {
        $customer = User::factory()->create();
        $serviceCenter = ServiceCenter::factory()->published()->create();
        $deletedReview = Review::factory()->for($customer)->for($serviceCenter)->create();
        $deletedReview->delete();

        $this->actingAs($customer)
            ->post(route('reviews.store', $serviceCenter->slug), [
                'rating' => 4,
                'comment' => 'تجربة جديدة',
            ])
            ->assertRedirect(route('service-centers.show', $serviceCenter->slug));

        $deletedReview->refresh();
        $this->assertNull($deletedReview->deleted_at);
        $this->assertSame(4, $deletedReview->rating);
        $this->assertSame('تجربة جديدة', $deletedReview->comment);
        $this->assertSame(ReviewStatus::Pending, $deletedReview->status);
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_public_page_escapes_published_review_and_hides_pending_review(): void
    {
        $serviceCenter = ServiceCenter::factory()->published()->create();
        Review::factory()->published()->for($serviceCenter)->create([
            'comment' => '<script>alert("review")</script>',
        ]);
        Review::factory()->for($serviceCenter)->create([
            'comment' => 'تعليق سري قيد المراجعة',
        ]);

        $this->get(route('service-centers.show', $serviceCenter->slug))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("review")</script>', false)
            ->assertDontSee('تعليق سري قيد المراجعة');
    }

    public function test_guest_is_redirected_to_login_when_creating_review(): void
    {
        $serviceCenter = ServiceCenter::factory()->published()->create();

        $this->post(route('reviews.store', $serviceCenter->slug), [
            'rating' => 5,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('reviews', 0);
    }
}
