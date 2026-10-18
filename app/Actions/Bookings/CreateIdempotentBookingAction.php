<?php

namespace App\Actions\Bookings;

use App\Data\Bookings\CreateBookingData;
use App\Http\Resources\Api\V1\BookingResource;
use App\Models\Booking;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Database\ConcurrencyErrorDetector;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

final class CreateIdempotentBookingAction
{
    public function __construct(private readonly CreateBookingAction $bookings) {}

    public function handle(User $customer, ServiceCenter $center, CreateBookingData $data, string $key): JsonResponse
    {
        $keyHash = hash('sha256', strtolower($key));
        $requestHash = $this->requestHash($center, $data);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                /** @var array{Booking, string} $created */
                $created = DB::transaction(function () use ($customer, $center, $data, $keyHash, $requestHash): array {
                    try {
                        $claimId = DB::table('idempotency_keys')->insertGetId([
                            'customer_id' => $customer->id,
                            'key_hash' => $keyHash,
                            'request_hash' => $requestHash,
                            'expires_at' => now()->addDay(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } catch (UniqueConstraintViolationException $exception) {
                        throw new IdempotencyClaimConflict(previous: $exception);
                    }

                    $booking = $this->bookings->createInTransaction(
                        $customer,
                        $center,
                        $data,
                        afterCenterLock: static function (ServiceCenter $lockedCenter): void {
                            ServiceCenter::query()->published()->publiclyAvailable()->whereKey($lockedCenter->id)->firstOrFail();
                            Gate::authorize('create', [Booking::class, $lockedCenter]);
                        },
                    );
                    $response = (new BookingResource($booking))->response()->setStatusCode(201);
                    $body = $response->getContent();
                    if (! is_string($body)) {
                        throw new RuntimeException('Could not serialize the booking response.');
                    }

                    DB::table('idempotency_keys')->where('id', $claimId)->update([
                        'booking_id' => $booking->id,
                        'response_status' => 201,
                        'response_body' => $body,
                        'updated_at' => now(),
                    ]);

                    return [$booking, $body];
                }, 3);

                [$booking, $body] = $created;
                $this->bookings->notifyCreated($booking);

                return JsonResponse::fromJsonString($body, 201);
            } catch (IdempotencyClaimConflict) {
                $existing = DB::table('idempotency_keys')
                    ->where('customer_id', $customer->id)
                    ->where('key_hash', $keyHash)
                    ->first();

                if ($existing === null) {
                    continue;
                }

                if ($existing->expires_at <= now()->format('Y-m-d H:i:s')) {
                    try {
                        DB::transaction(function () use ($existing): void {
                            $locked = DB::table('idempotency_keys')->where('id', $existing->id)->lockForUpdate()->first();
                            if ($locked !== null && $locked->expires_at <= now()->format('Y-m-d H:i:s')) {
                                DB::table('idempotency_keys')->where('id', $locked->id)->delete();
                            }
                        }, 3);
                    } catch (QueryException|DeadlockException $exception) {
                        if ((new ConcurrencyErrorDetector)->causedByConcurrencyError($exception)) {
                            return $this->retryableResponse();
                        }

                        throw $exception;
                    }

                    continue;
                }

                if (! hash_equals($existing->request_hash, $requestHash)) {
                    return response()->json([
                        'message' => 'مفتاح الطلب مستخدم بالفعل مع بيانات حجز مختلفة.',
                        'errors' => ['idempotency_key' => ['استخدم مفتاحًا جديدًا لطلب حجز مختلف.']],
                    ], 409);
                }

                if ($existing->booking_id === null) {
                    return response()->json(['message' => 'الحجز المرتبط بهذا المفتاح لم يعد متاحًا.'], 410);
                }

                if ((int) $existing->response_version !== 1) {
                    return response()->json(['message' => 'نسخة رد الحجز المحفوظ غير مدعومة.'], 409);
                }

                if ($existing->response_status === null || $existing->response_body === null) {
                    return $this->retryableResponse();
                }

                return JsonResponse::fromJsonString($existing->response_body, (int) $existing->response_status);
            } catch (QueryException|DeadlockException $exception) {
                if ((new ConcurrencyErrorDetector)->causedByConcurrencyError($exception)) {
                    return $this->retryableResponse();
                }

                throw $exception;
            }
        }

        return $this->retryableResponse();
    }

    private function requestHash(ServiceCenter $center, CreateBookingData $data): string
    {
        $scheduledAt = str_replace('T', ' ', $data->scheduledAt);
        $scheduledAt = strlen($scheduledAt) === 16 ? $scheduledAt.':00' : $scheduledAt;

        return hash('sha256', json_encode([
            'service_center_id' => $center->id,
            'service_id' => $data->serviceId,
            'scheduled_at' => $scheduledAt,
            'customer_phone' => $data->customerPhone,
            'notes' => $data->notes,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    private function retryableResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'طلب الحجز قيد المعالجة أو تعذر إكماله مؤقتًا. أعد المحاولة بنفس المفتاح.',
            'errors' => ['idempotency_key' => ['أعد المحاولة بنفس المفتاح.']],
        ], 503, ['Retry-After' => '1']);
    }
}
