<?php

namespace Tests\Feature\Web;

use App\Enums\BookingStatus;
use App\Mail\BookingCreatedConfirmation;
use App\Mail\NewBookingReceived;
use App\Models\Booking;
use App\Models\Service;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_customer_creates_pending_booking_from_center_page(): void
    {
        $this->travelTo('2026-09-24 10:00:00');
        $customer = User::factory()->create();
        $service = Service::factory()->create();
        $serviceCenter = ServiceCenter::factory()->published()->create();
        $serviceCenter->services()->attach($service);
        Mail::fake();

        $this->actingAs($customer)
            ->get(route('service-centers.show', $serviceCenter->slug))
            ->assertOk()
            ->assertSee('احجز موعدك مع المركز')
            ->assertSee(route('bookings.store', $serviceCenter->slug));

        $this->actingAs($customer)
            ->post(route('bookings.store', $serviceCenter->slug), [
                'service_id' => $service->id,
                'customer_phone' => '01012345678',
                'scheduled_at' => '2026-09-25T11:00',
                'notes' => 'صوت غير طبيعي من المحرك.',
            ])
            ->assertRedirect(route('bookings.index'))
            ->assertSessionHas('success', 'تم إرسال طلب الحجز إلى المركز بنجاح.');

        $this->assertDatabaseHas('bookings', [
            'customer_id' => $customer->id,
            'service_center_id' => $serviceCenter->id,
            'service_id' => $service->id,
            'customer_phone' => '01012345678',
            'scheduled_at' => '2026-09-25 11:00:00',
            'status' => BookingStatus::Pending->value,
        ]);

        Mail::assertQueued(
            NewBookingReceived::class,
            fn (NewBookingReceived $mail): bool => $mail->hasTo($serviceCenter->owner->email)
                && $mail->booking->service_center_id === $serviceCenter->id
                && $mail->queue === 'emails',
        );
        Mail::assertQueued(
            BookingCreatedConfirmation::class,
            fn (BookingCreatedConfirmation $mail): bool => $mail->hasTo($customer->email)
                && $mail->booking->service_center_id === $serviceCenter->id
                && $mail->queue === 'emails',
        );
    }

    public function test_store_rejects_past_date_and_invalid_phone(): void
    {
        $this->travelTo('2026-09-24 10:00:00');
        $customer = User::factory()->create();
        $serviceCenter = ServiceCenter::factory()->published()->create();
        $service = Service::factory()->create();
        $serviceCenter->services()->attach($service);

        $this->actingAs($customer)
            ->from(route('service-centers.show', $serviceCenter->slug))
            ->post(route('bookings.store', $serviceCenter->slug), [
                'service_id' => $service->id,
                'customer_phone' => '123',
                'scheduled_at' => '2026-09-23T11:00',
            ])
            ->assertRedirect(route('service-centers.show', $serviceCenter->slug))
            ->assertSessionHasErrors([
                'customer_phone' => 'أدخل رقم موبايل مصري صحيحًا.',
                'scheduled_at' => 'موعد الحجز يجب أن يكون في المستقبل.',
            ]);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_store_rejects_service_not_offered_by_center(): void
    {
        $customer = User::factory()->create();
        $serviceCenter = ServiceCenter::factory()->published()->create();
        $otherService = Service::factory()->create();
        Mail::fake();

        $this->actingAs($customer)
            ->post(route('bookings.store', $serviceCenter->slug), [
                'service_id' => $otherService->id,
                'customer_phone' => '01012345678',
                'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors([
                'service_id' => 'الخدمة المختارة غير متاحة في هذا المركز.',
            ]);

        $this->assertDatabaseCount('bookings', 0);
        Mail::assertNothingQueued();
    }

    public function test_customer_cannot_create_duplicate_active_booking_at_same_center_and_time(): void
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()->for($customer, 'customer')->create();

        $this->actingAs($customer)
            ->post(route('bookings.store', $booking->serviceCenter->slug), [
                'service_id' => $booking->service_id,
                'customer_phone' => '01012345678',
                'scheduled_at' => $booking->scheduled_at->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors([
                'scheduled_at' => 'لديك حجز قائم في نفس المركز وفي نفس الموعد.',
            ]);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_customer_lists_only_their_bookings_and_escaped_notes(): void
    {
        $customer = User::factory()->create();
        $ownBooking = Booking::factory()->for($customer, 'customer')->create([
            'notes' => '<script>alert("booking")</script>',
        ]);
        $otherBooking = Booking::factory()->create();

        $this->actingAs($customer)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertSee($ownBooking->serviceCenter->name)
            ->assertDontSee($otherBooking->serviceCenter->name)
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("booking")</script>', false);
    }

    public function test_customer_cancels_pending_own_booking(): void
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()->for($customer, 'customer')->create();

        $this->actingAs($customer)
            ->delete(route('bookings.destroy', $booking))
            ->assertRedirect()
            ->assertSessionHas('success', 'تم إلغاء الحجز بنجاح.');

        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertNotNull($booking->cancelled_at);
    }

    public function test_customer_cannot_cancel_another_customers_booking(): void
    {
        $customer = User::factory()->create();
        $otherBooking = Booking::factory()->create();

        $this->actingAs($customer)
            ->delete(route('bookings.destroy', $otherBooking))
            ->assertNotFound();

        $this->assertSame(BookingStatus::Pending, $otherBooking->fresh()->status);
    }

    public function test_customer_cannot_cancel_accepted_booking(): void
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()->accepted()->for($customer, 'customer')->create();

        $this->actingAs($customer)
            ->delete(route('bookings.destroy', $booking))
            ->assertForbidden();

        $this->assertSame(BookingStatus::Accepted, $booking->fresh()->status);
    }
}
