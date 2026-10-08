<?php

namespace Tests\Feature;

use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Support\Env;
use Pdo\Mysql;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DatabaseTlsConfigurationTest extends TestCase
{
    #[DataProvider('certificatePaths')]
    public function test_mysql_passes_the_configured_tls_options_to_pdo(?string $certificatePath, array $expectedOptions): void
    {
        $environment = Env::getRepository();
        $originalValue = $environment->get('MYSQL_ATTR_SSL_CA');

        try {
            if ($certificatePath === null) {
                $environment->clear('MYSQL_ATTR_SSL_CA');
            } else {
                $environment->set('MYSQL_ATTR_SSL_CA', $certificatePath);
            }

            $database = require config_path('database.php');
        } finally {
            if ($originalValue === null) {
                $environment->clear('MYSQL_ATTR_SSL_CA');
            } else {
                $environment->set('MYSQL_ATTR_SSL_CA', $originalValue);
            }
        }

        $options = (new MySqlConnector)->getOptions($database['connections']['mysql']);

        $this->assertSame($expectedOptions, array_intersect_key($options, [
            Mysql::ATTR_SSL_CA => true,
            Mysql::ATTR_SSL_VERIFY_SERVER_CERT => true,
        ]));
    }

    /**
     * @return array<string, array{?string, array<int, string|bool>}>
     */
    public static function certificatePaths(): array
    {
        return [
            'rds validates the server certificate' => ['/certificates/rds.pem', [
                Mysql::ATTR_SSL_CA => '/certificates/rds.pem',
                Mysql::ATTR_SSL_VERIFY_SERVER_CERT => true,
            ]],
            'local mysql without a certificate' => [null, []],
            'empty certificate setting' => ['', []],
        ];
    }
}
