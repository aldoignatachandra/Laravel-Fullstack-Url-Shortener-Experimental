<?php

namespace Tests\Unit;

use App\Services\ShortCodeService;
use PHPUnit\Framework\TestCase;

class ShortCodeServiceTest extends TestCase
{
    public function test_generates_six_character_code(): void
    {
        $code = ShortCodeService::generate();

        $this->assertSame(6, strlen($code));
    }

    public function test_code_is_alphanumeric(): void
    {
        $code = ShortCodeService::generate();

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $code);
    }

    public function test_generates_unique_codes(): void
    {
        $codes = collect(range(1, 100))->map(fn () => ShortCodeService::generate());

        $this->assertSame(100, $codes->unique()->count());
    }

    public function test_custom_length(): void
    {
        $code = ShortCodeService::generate(8);

        $this->assertSame(8, strlen($code));
    }
}
