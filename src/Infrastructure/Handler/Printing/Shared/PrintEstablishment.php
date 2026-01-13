<?php

namespace App\Infrastructure\Handler\Printing\Shared;

use Mike42\Escpos\Printer;

class PrintEstablishment
{
    protected Printer $printer;
    protected object $establishment;
    protected PrintText $printText;

    public function __construct(Printer $printer, object $establishment)
    {
        $this->printer = $printer;
        $this->establishment = $establishment;
        $this->printText = new PrintText($this->printer);
    }

    /**
     * @return void
     */
    public function printInfo(): void
    {
        $this->printText->new($this->establishment->address);
        $this->printText->new($this->establishment->location);
        if (isset($this->establishment->telephone)) {
            $this->printText->new("Telf.: {$this->establishment->telephone}");
        }
    }
}
