<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CheckScalingReadiness extends Command
{
    protected $signature = 'app:check-scaling-readiness';

    protected $description = 'Verify shared production state before starting an Auto Scaling instance';

    public function handle(): int
    {
        $databaseConnection = config('database.default');
        $databaseHost = config("database.connections.{$databaseConnection}.host", '');

        if ($databaseConnection !== 'mysql' || ! str_ends_with($databaseHost, '.rds.amazonaws.com')) {
            $this->error('Auto Scaling requires the shared RDS MySQL database.');

            return self::FAILURE;
        }

        if (config('session.driver') !== 'redis' || config('cache.default') !== 'redis' || config('queue.default') !== 'redis') {
            $this->error('Sessions, cache and queues must use shared Redis.');

            return self::FAILURE;
        }

        if (! str_starts_with(config('database.redis.default.host', ''), 'tls://') || ! str_starts_with(config('database.redis.cache.host', ''), 'tls://')) {
            $this->error('Shared Redis connections must use TLS.');

            return self::FAILURE;
        }

        if (config('filesystems.center_images_disk') !== 's3' || ! config('filesystems.disks.s3.bucket')) {
            $this->error('Center images must use the shared S3 bucket.');

            return self::FAILURE;
        }

        try {
            if (DB::table('center_images')->where('disk', '!=', 's3')->exists()) {
                $this->error('Migrate existing center image files and disk records to S3 before enabling Auto Scaling.');

                return self::FAILURE;
            }

            Redis::connection()->ping();
            Redis::connection('cache')->ping();
            Storage::disk('s3')->files('service-centers', false);
        } catch (Throwable) {
            $this->error('Shared database, Redis or S3 is unavailable. Verify connectivity and instance permissions.');

            return self::FAILURE;
        }

        $this->info('Shared state is ready for Auto Scaling.');

        return self::SUCCESS;
    }
}
