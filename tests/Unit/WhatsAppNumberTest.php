<?php

namespace Tests\Unit;

use App\Support\WhatsAppNumber;
use PHPUnit\Framework\TestCase;

class WhatsAppNumberTest extends TestCase
{
    public function test_nigerian_local_number_is_converted(): void
    {
        $this->assertSame('2349036518913', WhatsAppNumber::normalize('09036518913', 'Nigeria'));
        $this->assertSame('2349036518913', WhatsAppNumber::normalize('0903 651 8913', 'nigeria'));
    }

    public function test_blank_country_defaults_to_nigeria(): void
    {
        $this->assertSame('2349036518913', WhatsAppNumber::normalize('09036518913', null));
        $this->assertSame('2349036518913', WhatsAppNumber::normalize('09036518913', ''));
    }

    public function test_other_country_uses_its_own_code(): void
    {
        $this->assertSame('233244123456', WhatsAppNumber::normalize('0244123456', 'Ghana'));
    }

    public function test_international_formats_are_cleaned_and_kept(): void
    {
        $this->assertSame('2349036518913', WhatsAppNumber::normalize('+234 903 651 8913', 'Ghana'));
        $this->assertSame('2349036518913', WhatsAppNumber::normalize('002349036518913', 'Ghana'));
        $this->assertSame('2349036518913', WhatsAppNumber::normalize('2349036518913', 'Nigeria'));
    }

    public function test_unknown_country_or_empty_input_returns_null(): void
    {
        $this->assertNull(WhatsAppNumber::normalize('09036518913', 'Atlantis'));
        $this->assertNull(WhatsAppNumber::normalize('', 'Nigeria'));
        $this->assertNull(WhatsAppNumber::normalize(null, 'Nigeria'));
        $this->assertNull(WhatsAppNumber::normalize('abc', 'Nigeria'));
    }
}
