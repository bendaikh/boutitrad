<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Support\CathedisStatusMapper;
use PHPUnit\Framework\TestCase;

class CathedisStatusMapperTest extends TestCase
{
    public function test_maps_confirme_to_confirmee(): void
    {
        $this->assertSame(OrderStatus::Confirmee, CathedisStatusMapper::map('Confirmé'));
        $this->assertSame(OrderStatus::Confirmee, CathedisStatusMapper::map('CONFIRME'));
    }

    public function test_maps_livre_to_livree(): void
    {
        $this->assertSame(OrderStatus::Livree, CathedisStatusMapper::map('Livré'));
    }

    public function test_ignores_en_attente_ramassage(): void
    {
        $this->assertNull(CathedisStatusMapper::map('En Attente Ramassage'));
    }

    public function test_should_apply_confirmee_from_en_cours(): void
    {
        $this->assertTrue(CathedisStatusMapper::shouldApply(OrderStatus::EnCours, OrderStatus::Confirmee));
    }

    public function test_should_not_downgrade_from_expediee(): void
    {
        $this->assertFalse(CathedisStatusMapper::shouldApply(OrderStatus::Expediee, OrderStatus::Confirmee));
        $this->assertFalse(CathedisStatusMapper::shouldApply(OrderStatus::Expediee, OrderStatus::EnPreparation));
    }

    public function test_terminal_statuses_always_apply(): void
    {
        $this->assertTrue(CathedisStatusMapper::shouldApply(OrderStatus::Expediee, OrderStatus::Livree));
        $this->assertTrue(CathedisStatusMapper::shouldApply(OrderStatus::Confirmee, OrderStatus::Annulee));
    }

    public function test_display_color_for_confirmed_status(): void
    {
        $this->assertSame('indigo', CathedisStatusMapper::displayColor('Confirmé'));
        $this->assertSame('green', CathedisStatusMapper::displayColor('Livré'));
        $this->assertSame('red', CathedisStatusMapper::displayColor('Annulé Au Téléphone'));
        $this->assertSame('yellow', CathedisStatusMapper::displayColor('Client Injoignable CRC'));
        $this->assertSame('blue', CathedisStatusMapper::displayColor('En Attente Ramassage'));
    }

    public function test_is_confirmed(): void
    {
        $this->assertTrue(CathedisStatusMapper::isConfirmed('Confirmé'));
        $this->assertFalse(CathedisStatusMapper::isConfirmed('En Attente Ramassage'));
    }
}
