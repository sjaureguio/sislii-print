<?php

namespace App\Infrastructure\Handler\Printing\Sections;

use App\Infrastructure\Handler\Printing\Shared\PrintText;
use Mike42\Escpos\Printer;

/**
 * SRP: responsable exclusivamente del resumen final del cuadre de caja.
 * Todos los valores vienen ya calculados desde el backend.
 */
class FinalSummarySection extends AbstractSection
{
    private const int LABEL_WIDTH = 26;
    private const int VALUE_WIDTH = 22;

    public function print(Printer $printer, PrintText $text, object $data): void
    {
        $income = $data->income;
        $expense = $data->expense;
        $totals = $data->totals;
        $opening = $data->cash_box;
        $difference = floatval($opening->difference);

        $printer->setFont();
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setDoubleStrike();
        $text($this->separator);
        $text('== DINERO EN CAJA ==');
        $text($this->separator);
        $printer->setDoubleStrike(false);
        $printer->setJustification();

        $lw = self::LABEL_WIDTH;
        $vw = self::VALUE_WIDTH;

        if ($data->include_init_bal) {
            $this->printRow($text, 'CAJA CHICA', floatval($income->opening_amount), $lw, $vw);
        }
        $this->printRow($text, '(+) VENTAS EN EFECTIVO', floatval($income->sales_in_cash), $lw, $vw);
        $this->printRow($text, '(+) OTROS INGRESOS', floatval($income->mov_income_cash), $lw, $vw);
        $this->printRow($text, '(-) COMPRAS EN EFECTIVO', floatval($expense->purchase_in_cash), $lw, $vw);
        $this->printRow($text, '(-) OTROS EGRESOS', floatval($expense->mov_expense_cash), $lw, $vw);
        $text($this->separator, false);

        $printer->setTextSize(1, 2);
        $this->printRow($text, 'EFECTIVO EN CAJA', floatval($opening->closing_amount), $lw, $vw);
        $printer->setTextSize(1, 1);
        $printer->setDoubleStrike(false);

        $printer->setFont();
        $printer->setDoubleStrike();
        $this->printRow($text, 'EFECTIVO ESPERADO', floatval($opening->expected_amount), $lw, $vw);
        $printer->feed();
        $text($this->separator, false);

        $printer->setFont();
        $printer->setDoubleStrike();
        $this->printRow($text, $difference > 0 ? 'SOBRANTE' : 'FALTANTE', abs($difference), $lw, $vw);
        $printer->feed();
        $text($this->separator, false);
    }
}
