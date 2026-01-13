<?php

namespace App\Infrastructure\Handler\Printing\Shared;

use App\Domain\Printing\Printing;
use Exception;
use Mike42\Escpos\Printer;

class Payment
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
    public function printPayments(): void
    {
        $printText = new PrintText($this->printer);

        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->setDoubleStrike();
        $printText('METODOS DE PAGO');
        $this->printer->setDoubleStrike(false);
        $this->printer->setJustification();

        $printText($this->separator, false);
        $printText('N°   M. PAGO                                               MONTO');

        $printText($this->separator, false);

        foreach ($this->document->payments as $key => $payment) {
            $feeNumber = str_pad((string)++$key, 5);

            $payMethod = str_pad($payment->payment_method_type->description, 30);
            $fmtAmount = number_format(floatval($payment->payment), 2);
            $amount = str_pad($fmtAmount, 29, ' ', STR_PAD_LEFT);
            $printText($feeNumber . $payMethod . $amount);
        }
        $printText($this->separator, false);
    }
}
