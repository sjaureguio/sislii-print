<?php

namespace App\Infrastructure\Handler\Printing\Sections;

use App\Infrastructure\Handler\Printing\Shared\PrintText;
use DateMalformedStringException;
use DateTimeImmutable;
use Mike42\Escpos\Printer;

/**
 * SRP: responsable exclusivamente de la sección de ingresos.
 * Muestra ventas por métdo de pago y movimientos de ingreso.
 */
class IncomeSection extends AbstractSection
{
    public function print(Printer $printer, PrintText $text, object $data): void
    {
        $income = $data->income;

        $printer->feed();
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize(2, 1);
        $text('INGRESOS');
        $printer->setTextSize(1, 1);
        $printer->setJustification();

        $this->printSalesByPaymentMethod($printer, $text, $income->sales->by_payment_method);
        $this->printTotals($printer, $text, $data);

        if (!empty($income->movements->details)) {
            $this->printIncomeMovements($printer, $text, $income->movements->details);
        }
    }

    private function printSalesByPaymentMethod(Printer $printer, PrintText $text, array $methods): void
    {
        $printer->feed();
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $text($this->separator, false);
        $text('VENTAS POR METODO DE PAGO');
        $text($this->separator, false);
        $printer->setJustification();
        $text('M. PAGO                  OPERAC.           MONTO');
        $text($this->separator, false);
        $printer->setDoubleStrike(false);

        foreach ($methods as $pay) {
            $method = str_pad($pay->payment_method, 16);
            $count  = str_pad((string)$pay->payment_count, 16, ' ', STR_PAD_LEFT);
            $amount = str_pad($this->formatAmount(floatval($pay->total_amount)), 16, ' ', STR_PAD_LEFT);
            $text($method . $count . $amount);
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    private function printIncomeMovements(Printer $printer, PrintText $text, array $movements): void
    {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $text($this->separator, false);
        $printer->setDoubleStrike();
        $text('OTROS INGRESOS');
        $printer->setDoubleStrike(false);
        $text($this->separator, false);
        $printer->setJustification();

        foreach ($movements as $key => $mov) {
            $numeration = $key + 1;
            $printNum = str_pad("$numeration)", 4);
            $method = str_pad($mov->pay_method, 12);

            $createdAt = str_pad((new DateTimeImmutable((string) $mov->created_at))->format('d/m/Y H:i'), 19);
            $amount = str_pad($this->formatAmount(floatval($mov->amount)), 13, ' ', STR_PAD_LEFT);
            $text($printNum . $method . $createdAt . $amount, false);
            $text(str_pad($mov->reason, 48), false);
        }

        $printer->feed();
    }

    private function printTotals(Printer $printer, PrintText $text, object $data): void
    {
        $text($this->separator, false);
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $grandTotal = floatval($data->income->sales->total_sales);
        $printer->setFont();
        $printer->setDoubleStrike();
        $text('TOTAL VENTAS: ' . $this->formatAmount($grandTotal));
        $printer->setDoubleStrike(false);
        $printer->setJustification();
        $printer->feed();
    }
}
