<?php

namespace App\Actions\Bookings;

use RuntimeException;

final class IdempotencyClaimConflict extends RuntimeException {}
