<?php

namespace Tests\Unit;

use App\Support\OfferingGuard;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OfferingGuardTest extends TestCase
{
    public function test_it_detects_profanity_in_names(): void
    {
        $this->assertTrue(OfferingGuard::containsProfanity('what the fuck'));
    }

    public function test_it_allows_respectful_names(): void
    {
        $this->assertFalse(OfferingGuard::containsProfanity('Tenzin'));
        $this->assertSame('Tenzin', OfferingGuard::assertCleanName('Tenzin'));
    }

    public function test_it_rejects_profanity_in_names(): void
    {
        $this->expectException(ValidationException::class);

        OfferingGuard::assertCleanName('bad shit name');
    }
}
