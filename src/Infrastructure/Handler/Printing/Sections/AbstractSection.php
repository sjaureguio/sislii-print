<?php

namespace App\Infrastructure\Handler\Printing\Sections;

use App\Infrastructure\Handler\Printing\Contracts\BalanceSectionPrinterInterface;
use App\Infrastructure\Handler\Printing\Shared\PrintText;
use Mike42\Escpos\Printer;

/**
 * Clase base para todas las secciones del cuadre de caja.
 *
 * DRY: centraliza el constructor, el separador y los helpers
 *      repetidos en todas las secciones (printTitle, printSectionTotal,
 *      formatAmount, formatRow).
 * LSP: las subclases solo implementan su lógica propia, son
 *      100% sustituibles por BalanceSectionPrinterInterface.
 */
abstract class AbstractSection implements BalanceSectionPrinterInterface
{
    protected string $separator;

    public function __construct(string $separator)
    {
        $this->separator = $separator;
    }

    // ── Helpers reutilizables ────────────────────────────────────────────────

    /**
     * Imprime un título de sección centrado y en negrita.
     */
    protected function printTitle(Printer $printer, PrintText $text, string $title): void
    {
        $printer->setFont();
        $printer->setDoubleStrike();
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $text($title);
        $printer->setDoubleStrike(false);
        $printer->setJustification();
    }

    /**
     * Imprime una línea de total alineada a la derecha en negrita,
     * seguida de un feed. Patrón común a Income, Expense, Discounts y Courtesies.
     */
    protected function printSectionTotal(
        Printer $printer,
        PrintText $text,
        string $label,
        float $amount
    ): void {
        $text($this->separator);
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->setFont();
        $printer->setDoubleStrike();
        $text($label . number_format($amount, 2));
        $printer->setDoubleStrike(false);
        $printer->setJustification();
        $printer->feed();
    }

    /**
     * Formatea un monto monetario en soles.
     */
    protected function formatAmount(float $amount): string
    {
        return 'S/ ' . number_format($amount, 2);
    }

    /**
     * Imprime una fila etiqueta–valor alineada en columnas fijas.
     * Usado en FinalSummarySection.
     */
    protected function printRow(
        PrintText $text,
        string $label,
        float $value,
        int $labelWidth,
        int $valueWidth
    ): void {
        $text(
            str_pad($label, $labelWidth) .
            str_pad($this->formatAmount($value), $valueWidth, ' ', STR_PAD_LEFT)
        );
    }
}
