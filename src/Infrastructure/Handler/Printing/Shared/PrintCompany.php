<?php

namespace App\Infrastructure\Handler\Printing\Shared;

use Exception;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\Printer;

class PrintCompany
{
    protected Printer $printer;
    protected object $company;
    protected PrintText $printText;

    public function __construct(Printer $printer, object $company)
    {
        $this->printer = $printer;
        $this->company = $company;
        $this->printText = new PrintText($this->printer);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function printLogo(): void
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);

        $publicDir = __DIR__ . '/../../../../../public';
        $filename = $publicDir . '/images/logo.png';

        if (file_exists($filename)) {
            $logo = EscposImage::load($filename, false);
            $this->printer->bitImage($logo);
        }
    }

    /**
     * @return void
     */
    public function printInfo(): void
    {
        $this->printer->setDoubleStrike();
        $this->printer->setFont();
        $this->printText->new($this->company->trade_name);
        $this->printer->setDoubleStrike(false);
        if (substr($this->company->number, 0, -9) == '10') {
             $this->printText->new('DE: ' . $this->company->name);
        }
        $this->printText->new('R.U.C.: ' . $this->company->number);
    }
}
