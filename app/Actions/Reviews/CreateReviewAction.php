<?php

namespace App\Actions\Reviews;

use App\Data\Reviews\StoreReviewData;
use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateReviewAction
{
    public function handle(User $customer, ServiceCenter $serviceCenter, StoreReviewData $data): Review
    {
        try {
            return DB::transaction(function () use ($customer, $serviceCenter, $data): Review {
                $existingReview = Review::query()
                    ->withTrashed()
                    ->whereBelongsTo($customer)
                    ->whereBelongsTo($serviceCenter)
                    ->lockForUpdate()
                    ->first();

                if ($existingReview?->trashed() === false) {
                    throw ValidationException::withMessages([
                        'rating' => 'لقد أضفت تقييمًا لهذا المركز من قبل.',
                    ]);
                }

                if ($existingReview !== null) {
                    $existingReview->restore();
                    $existingReview->update($this->attributes($data));

                    return $existingReview->refresh()->load(['user:id,name', 'serviceCenter:id,name,slug']);
                }

                $review = $customer->reviews()->create([
                    'service_center_id' => $serviceCenter->id,
                    ...$this->attributes($data),
                ]);

                return $review->load(['user:id,name', 'serviceCenter:id,name,slug']);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'rating' => 'لقد أضفت تقييمًا لهذا المركز من قبل.',
            ]);
        }
    }

    /** @return array{rating: int, comment: ?string, status: ReviewStatus, published_at: null} */
    private function attributes(StoreReviewData $data): array
    {
        return [
            'rating' => $data->rating,
            'comment' => $data->comment,
            'status' => ReviewStatus::Pending,
            'published_at' => null,
        ];
    }
}
