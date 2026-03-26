<?php

namespace App\Infrastructure\Handler\Printing\Sections;

use App\Infrastructure\Handler\Printing\Shared\PrintText;
use Mike42\Escpos\Printer;

/**
 * SRP: responsable exclusivamente de la sección de productos vendidos.
 * Muestra el detalle por producto agrupado por categoría,
 * con subtotal, descuentos, cortesías y total neto vendido.
 */
class ProductsSoldSection extends AbstractSection
{
    // --- Constantes para el formato de la tabla de categorías ---
    private const int CAT_NAME_WIDTH = 24;
    private const int CAT_QTY_WIDTH = 6;
    private const int CAT_AMOUNT_WIDTH = 10;
    private const int CAT_PERCENT_WIDTH = 8;

    public function print(Printer $printer, PrintText $text, object $data): void
    {
        $productsSold = $data->products_sold;

        $printer->feed();
        $text($this->separator, false);
        $this->printTitle($printer, $text, 'PRODUCTOS VENDIDOS');
        $this->printProductTableHeader($printer, $text);
        $this->printProductItems($text, $productsSold->items);
        $this->printTotals($printer, $text, $productsSold->totals);

        $this->printSalesByCategorySection($printer, $text, $productsSold);
    }

    private function printProductTableHeader(Printer $printer, PrintText $text): void
    {
        $printer->setJustification();
        $text($this->separator, false);
        $text('CANT PRODUCTO                       P.U.   TOTAL');
        $text($this->separator, false);
        $printer->setDoubleStrike(false);
    }

    private function printProductItems(PrintText $text, array $items): void
    {
        foreach ($items as $item) {
            $qty = str_pad($item->quantity_sold, 5);
            $product = mb_substr($item->product_name, 0, 28);
            $product = $product . str_repeat(' ', 28 - mb_strlen($product));
            $unitP = str_pad(number_format((float)$item->unit_price, 2), 7, ' ', STR_PAD_LEFT);
            $total = str_pad(number_format((float)$item->total, 2), 8, ' ', STR_PAD_LEFT);
            $text($qty . $product . $unitP . $total);
        }
    }

    private function printTotals(Printer $printer, PrintText $text, object $totals): void
    {
        $text($this->separator, false);
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $grandTotal = floatval($totals->grand_total);
        $printer->setFont();
        $printer->setDoubleStrike();
        $text('TOTAL VENDIDO: ' . $this->formatAmount($grandTotal));
        $printer->setDoubleStrike(false);
        $printer->setJustification();
        $printer->feed();
    }

    // --- Sección de Ventas por Categoría Refactorizada ---

    private function printSalesByCategorySection(Printer $printer, PrintText $text, object $productsSold): void
    {
        $text($this->separator, false);
        $this->printTitle($printer, $text, 'VENTAS POR CATEGORIA');

        $categorySales = $this->calculateCategorySales($productsSold->items);
        $grandTotal = (float) $productsSold->totals->grand_total;

        $this->printCategoryTableHeader($text);
        $this->printCategoryTableRows($text, $categorySales, $grandTotal);

        $text($this->separator, false);
        $printer->feed();
    }

    /**
     * Calcula y agrupa las ventas por categoría.
     * Pura lógica de negocio, sin formato.
     */
    private function calculateCategorySales(array $items): array
    {
        $categories = [];
        foreach ($items as $item) {
            $categoryName = $item->category_name;
            if (!isset($categories[$categoryName])) {
                $categories[$categoryName] = ['quantity_sold' => 0, 'total' => 0];
            }
            $categories[$categoryName]['quantity_sold'] += $item->quantity_sold;
            $categories[$categoryName]['total'] += $item->total;
        }
        return $categories;
    }

    /**
     * Imprime el encabezado de la tabla de categorías.
     */
    private function printCategoryTableHeader(PrintText $text): void
    {
        $text($this->separator, false);
        $header = str_pad('CATEGORIA', self::CAT_NAME_WIDTH)
                . str_pad('CANT.', self::CAT_QTY_WIDTH, ' ', STR_PAD_LEFT)
                . str_pad('IMPORTE', self::CAT_AMOUNT_WIDTH, ' ', STR_PAD_LEFT)
                . str_pad('%', self::CAT_PERCENT_WIDTH, ' ', STR_PAD_LEFT);
        $text($header);
        $text($this->separator, false);
    }

    /**
     * Itera sobre las ventas por categoría e imprime cada fila.
     */
    private function printCategoryTableRows(PrintText $text, array $categorySales, float $grandTotal): void
    {
        foreach ($categorySales as $categoryName => $data) {
            $line = $this->formatCategoryRow($categoryName, $data, $grandTotal);
            $text($line);
        }
    }

    /**
     * Formatea una única línea de la tabla de categorías.
     */
    private function formatCategoryRow(string $name, array $data, float $grandTotal): string
    {
        // Solución para caracteres multibyte como 'Ñ'
        $nameTruncated = mb_substr($name, 0, self::CAT_NAME_WIDTH);
        $namePadded = $nameTruncated . str_repeat(' ', self::CAT_NAME_WIDTH - mb_strlen($nameTruncated));

        $quantityPadded = str_pad($data['quantity_sold'], self::CAT_QTY_WIDTH, ' ', STR_PAD_LEFT);
        $totalPadded = str_pad(number_format($data['total'], 2), self::CAT_AMOUNT_WIDTH, ' ', STR_PAD_LEFT);

        $percentage = $grandTotal > 0 ? ($data['total'] / $grandTotal) * 100 : 0;
        $percentageFormatted = number_format($percentage, 2) . '%';
        $percentagePadded = str_pad($percentageFormatted, self::CAT_PERCENT_WIDTH, ' ', STR_PAD_LEFT);

        return $namePadded . $quantityPadded . $totalPadded . $percentagePadded;
    }
}
