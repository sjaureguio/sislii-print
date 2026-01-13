<?php

namespace App\Infrastructure\Handler\Printing\Shared;

use App\Domain\Printing\Printing;
use DateTime;
use Exception;
use Mike42\Escpos\Printer;

class PrintCredit
{
    protected string $separator;

    public function __construct(
        protected Printer $printer,
        protected object $document,
    ) {

        $this->separator = Printing::SEPARATOR;
    }

    /**
     * @throws Exception
     */
    public function printCreditInfo(): void
    {
        $printText = new PrintText($this->printer);
        $debt = $this->document->debt;
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->setDoubleStrike();
        $printText('INFORMACION DE CREDITO');
        $this->printer->setDoubleStrike(false);
        $this->printer->setJustification();

        $printText($this->separator, false);
        $printText('TOTAL CRÉDITO: ' . number_format($debt->total, 2));
        $printText('INICIAL: ' . number_format($debt->initial_payment, 2));
        $printText('POR PAGAR: ' . number_format($debt->total - $debt->initial_payment, 2));

        $printText($this->separator, false);
        $printText('N° CUOTA            F. VENCIMIENTO                         MONTO');

        $printText($this->separator, false);

        foreach ($this->document->fees as $key => $fee) {
            $feeNumber = str_pad((string)++$key, 20);
            $date = new DateTime($fee->date_of_due);
            $date = str_pad($date->format('d/m/Y'), 24);
            $fmtAmount = number_format(floatval($fee->amount), 2);
            $amount = str_pad($fmtAmount, 20, ' ', STR_PAD_LEFT);
            $printText($feeNumber . $date . $amount);
        }
        $printText($this->separator, false);
    }
}
