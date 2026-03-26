<?php

namespace App\Infrastructure\Handler\Printing\Sections;

use App\Infrastructure\Handler\Printing\Shared\PrintText;
use DateMalformedStringException;
use DateTimeImmutable;
use Mike42\Escpos\Printer;

/**
 * SRP: responsable exclusivamente de la sección de egresos.
 * Muestra compras por método de pago y movimientos de egreso.
 */
class ExpenseSection extends AbstractSection
{
    /**
     * @throws DateMalformedStringException
     */
    public function print(Printer $printer, PrintText $text, object $data): void
    {
        $expense      = $data->expense;
        $hasPurchases = !empty($expense->purchases->by_payment_method);
        $hasMovements = !empty($expense->movements->details);

        $printer->feed();
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize(2, 1);
        $printer->setDoubleStrike();
        $text('EGRESOS');
        $printer->setDoubleStrike(false);
        $printer->setTextSize(1, 1);
        $printer->setJustification();

        if ($hasPurchases) {
            $this->printPurchases($printer, $text, $expense->purchases->by_payment_method);
        }
        if ($hasMovements) {
            $this->printExpenseMovements($printer, $text, $expense->movements->details);
        }

        $this->printSectionTotal($printer, $text, 'TOTAL EGRESOS  : S/ ', floatval($data->totals->total_expense));


        $this->printOtherOperations($printer, $text, $data);
    }

    private function printPurchases(Printer $printer, PrintText $text, array $purchases): void
    {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $text($this->separator, false);
        $printer->setDoubleStrike();
        $text('COMPRAS');
        $printer->setDoubleStrike(false);
        $text($this->separator, false);
        $printer->setJustification();

        foreach ($purchases as $purch) {
            $method = str_pad($purch->payment_method, 30);
            $amount = str_pad($this->formatAmount(floatval($purch->total_amount)), 18, ' ', STR_PAD_LEFT);
            $text($method . $amount);
        }

        $printer->feed();
    }

    /**
     * @throws DateMalformedStringException
     */
    private function printExpenseMovements(Printer $printer, PrintText $text, array $movements): void
    {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $text($this->separator, false);
        $printer->setDoubleStrike();
        $text('OTROS EGRESOS');
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

    private function printOtherOperations(Printer $printer, PrintText $text, object $data): void
    {
        $printer->feed();
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize(2, 1);
        $printer->setDoubleStrike();
        $text('OTRAS OPERACIONES');
        $printer->setDoubleStrike(false);
        $printer->setTextSize(1, 1);
        $printer->setJustification();

        $text($this->separator, false);
        $printer->feed();
        $discountName = str_pad('DESCUENTOS', 30);
        $discountAmount = str_pad(
            $this->formatAmount(floatval($data->discounts->total_discounts_amount)),
            18,
            ' ',
            STR_PAD_LEFT
        );
        $text($discountName . $discountAmount);

        $discountName = str_pad('CORTESIAS', 30);
        $discountAmount = str_pad(
            $this->formatAmount(floatval($data->courtesies->total_courtesies_amount)),
            18,
            ' ',
            STR_PAD_LEFT
        );
        $text($discountName . $discountAmount);

        $fullTotal = array_reduce(
            $data->cancellations->full_cancellations,
            fn ($sum, $c) => $sum + $c->total_amount,
            0
        );

        $partialTotal = array_reduce(
            $data->cancellations->partial_cancellations,
            fn ($sum, $c) => $sum + (float) $c->amount,
            0
        );

        $totalCancellationsAmount = $fullTotal + $partialTotal;
        $discountName = str_pad('CANCELACIONES', 30);
        $discountAmount = str_pad(
            $this->formatAmount(floatval($totalCancellationsAmount)),
            18,
            ' ',
            STR_PAD_LEFT
        );
        $text($discountName . $discountAmount);
        $text($this->separator, false);
    }
}
