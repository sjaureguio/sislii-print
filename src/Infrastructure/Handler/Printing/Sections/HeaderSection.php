<?php

namespace App\Infrastructure\Handler\Printing\Sections;

use App\Infrastructure\Handler\Printing\Shared\PrintText;
use Mike42\Escpos\Printer;

/**
 * SRP: responsable exclusivamente del encabezado del cuadre de caja.
 */
class HeaderSection extends AbstractSection
{
    public function print(Printer $printer, PrintText $text, object $data): void
    {
        $opening = $data->cash_box;
        // $income  = $data->income;

        $printer->setFont();
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setDoubleStrike();
        $text($this->separator, false);
        $printer->setTextSize(2, 1);
        $text('ARQUEO DE CAJA');
        $printer->setTextSize(1, 1);
        $text($this->separator);
        $printer->setDoubleStrike(false);
        $printer->setJustification();
        $printer->feed();

        $dateOpening = date('d/m/Y H:i A', strtotime($opening->opening_time));
        $dateClosing = date('d/m/Y H:i A', strtotime($opening->closing_time));

        $text('CAJA           : ' . $opening->cash_name);
        $text('CAJERO         : ' . $opening->user);
        $text('FECHA APERTURA : ' . $dateOpening);
        $text('FECHA CIERRE   : ' . $dateClosing);
        $printer->feed();
    }
}
