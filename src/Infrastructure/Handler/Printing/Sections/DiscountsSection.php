<?php

namespace App\Infrastructure\Handler\Printing\Sections;

use App\Infrastructure\Handler\Printing\Shared\PrintText;
use Mike42\Escpos\Printer;

/**
 * SRP: responsable exclusivamente de la sección de descuentos aplicados.
 * Muestra descuentos por orden y por ítem con detalle de usuario y motivo.
 */
class DiscountsSection extends AbstractSection
{
    public function print(Printer $printer, PrintText $text, object $data): void
    {
        $discounts      = $data->discounts;
        $totalDiscounts = floatval($discounts->total_discounts_amount ?? 0);
        $hasOrderDisc   = !empty($discounts->order_discounts);
        $hasItemDisc    = !empty($discounts->item_discounts);

        if ($totalDiscounts <= 0 && !$hasOrderDisc && !$hasItemDisc) {
            return;
        }

        $this->printTitle($printer, $text, 'DESCUENTOS APLICADOS');

        if ($hasOrderDisc) {
            $this->printOrderDiscounts($printer, $text, $discounts->order_discounts);
        }

        if ($hasItemDisc) {
            $this->printItemDiscounts($printer, $text, $discounts->item_discounts);
        }

        $this->printSectionTotal($printer, $text, 'TOTAL DESCUENTOS: S/ ', $totalDiscounts);
    }

    private function printOrderDiscounts(Printer $printer, PrintText $text, array $orderDiscounts): void
    {
        $text($this->separator);
        $text('  DESCUENTOS POR ORDEN');
        $text($this->separator);
        $text('N°   DESCRIPCION         ORDEN   USUARIO         MONTO');
        $text($this->separator);
        $printer->setDoubleStrike(false);

        foreach ($orderDiscounts as $key => $disc) {
            $counter  = str_pad((string)($key + 1), 4);
            $discName = str_pad(substr($disc->discount_name, 0, 18), 18);
            $orderN   = str_pad('#' . $disc->order_number, 7);
            $user     = str_pad(substr($disc->applied_by, 0, 13), 13);
            $amount   = str_pad($this->formatAmount(floatval($disc->discount_amount)), 9, ' ', STR_PAD_LEFT);
            $text($counter . $discName . $orderN . $user . $amount);

            if (!empty($disc->reason)) {
                $text('     Motivo: ' . substr($disc->reason, 0, 38));
            }
        }
    }

    private function printItemDiscounts(Printer $printer, PrintText $text, array $itemDiscounts): void
    {
        $text($this->separator);
        $text('  DESCUENTOS POR PRODUCTO');
        $text($this->separator);
        $printer->setDoubleStrike(false);

        foreach ($itemDiscounts as $key => $disc) {
            $counter = str_pad((string)($key + 1), 4);
            $product = str_pad(substr($disc->item_name ?? '', 0, 22), 22);
            $orderN  = str_pad('#' . $disc->order_number, 7);
            $amount  = str_pad($this->formatAmount(floatval($disc->discount_amount)), 15, ' ', STR_PAD_LEFT);
            $text($counter . $product . $orderN . $amount);
        }
    }
}
