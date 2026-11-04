<?php

namespace Tests\Unit\FamilyPack;

use App\Support\PackSize;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PackSizeTest extends TestCase
{
    public static function labels(): array
    {
        return [
            '500g'            => ['500g', 500.0, null, 'label'],
            '500 g spaced'    => ['500 g', 500.0, null, 'label'],
            '1kg'             => ['1kg', 1000.0, null, 'label'],
            '1 Kg capital'    => ['1 Kg', 1000.0, null, 'label'],
            '5 KG'            => ['5 KG', 5000.0, null, 'label'],
            '250gm'           => ['250gm', 250.0, null, 'label'],
            '0.5 kg'          => ['0.5 kg', 500.0, null, 'label'],
            '1,5 kg comma'    => ['1,5 kg', 1500.0, null, 'label'],
            'pack of 500g'    => ['Pack of 500g', 500.0, null, 'label'],
            '1 L'             => ['1 L', null, 1000.0, 'label'],
            '500ml'           => ['500ml', null, 500.0, 'label'],
            '1 Litre'         => ['1 Litre', null, 1000.0, 'label'],
            'bare 250'        => ['250', 250.0, null, 'inferred'],
            'bare 500'        => ['500', 500.0, null, 'inferred'],
            'bare 1'          => ['1', 1000.0, null, 'inferred'],
        ];
    }

    #[DataProvider('labels')]
    public function test_label_parsing(string $label, ?float $grams, ?float $ml, string $basis): void
    {
        $r = PackSize::parse($label);
        $this->assertNotNull($r, "'{$label}' should parse");
        $this->assertSame($grams, $r['grams']);
        $this->assertSame($ml, $r['ml']);
        $this->assertSame($basis, $r['basis']);
    }

    public function test_explicit_net_quantity_beats_the_label(): void
    {
        $r = PackSize::parse('Big pack', 5, 'kg');
        $this->assertSame(5000.0, $r['grams']);
        $this->assertSame('explicit', $r['basis']);

        $oil = PackSize::parse('whatever', 1, 'L');
        $this->assertSame(1000.0, $oil['ml']);
        $this->assertNull($oil['grams']);
    }

    public function test_unknown_pack_sizes_are_not_guessed(): void
    {
        $this->assertNull(PackSize::parse(null));
        $this->assertNull(PackSize::parse(''));
        $this->assertNull(PackSize::parse('pack'));
        $this->assertNull(PackSize::parse('1 pack'));
        $this->assertNull(PackSize::parse('60 tablets'));
        $this->assertNull(PackSize::parse('12 pcs'));
        $this->assertNull(PackSize::parse('x', 3, 'pcs'));
    }

    public function test_kilograms_and_litres_are_never_confused(): void
    {
        $this->assertNull(PackSize::parse('1 L')['grams']);
        $this->assertNull(PackSize::parse('1 kg')['ml']);
    }

    public function test_display_text(): void
    {
        $this->assertSame('500 g', PackSize::parse('500g')['text']);
        $this->assertSame('1 kg', PackSize::parse('1kg')['text']);
        $this->assertSame('1.5 kg', PackSize::parse('1500g')['text']);
        $this->assertSame('1 L', PackSize::parse('1000ml')['text']);
    }
}
