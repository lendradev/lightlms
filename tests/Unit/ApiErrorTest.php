<?php

namespace Tests\Unit;

use App\Support\ApiError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ApiErrorTest extends TestCase
{
    #[DataProvider('statusCodes')]
    public function test_it_maps_a_status_to_a_machine_readable_code(int $status, string $expected): void
    {
        $this->assertSame($expected, ApiError::codeFor($status));
    }

    /**
     * @return array<int, array{0: int, 1: string}>
     */
    public static function statusCodes(): array
    {
        return [
            [401, 'unauthenticated'],
            [403, 'forbidden'],
            [404, 'not_found'],
            [405, 'method_not_allowed'],
            [419, 'session_expired'],
            [422, 'validation_failed'],
            [429, 'rate_limited'],
            [400, 'request_failed'],
            [500, 'server_error'],
            [503, 'server_error'],
        ];
    }
}
