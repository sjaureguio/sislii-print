<?php

namespace App\Infrastructure\Handler\Printing\Shared;

use Mike42\Escpos\Printer;

class PrintCustomer
{
    protected Printer $printer;
    protected object $customer;
    protected bool $fromWeb;
    protected PrintText $printText;

    public function __construct(Printer $printer, object $customer, bool $fromWeb)
    {
        $this->printer = $printer;
        $this->customer = $customer;
        $this->fromWeb = $fromWeb;
        $this->printText = new PrintText($this->printer);
    }

    /**
     * @return void
     */
    public function printInfo(): void
    {
        $this->printer->setJustification();

        $this->printText->new('CLIENTE: ' . $this->customer->name);

        $this->printText->new("{$this->customer->identity_document->name}: {$this->customer->number}");
        if (isset($this->customer->address)) {
            $this->printText->new("DIRECCION: {$this->customer->address}");
        }
    }
}
