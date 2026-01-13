<?php

namespace App\Infrastructure\Handler\Printing\Shared;

use App\Domain\Printing\Printing;
use Mike42\Escpos\Printer;

class PrintInvoiceType
{
    protected PrintText $printText;
    protected string $separator;

    public function __construct(
        protected Printer $printer,
        protected object $document,
    ) {
        $this->printText = new PrintText($this->printer);
        $this->separator = Printing::SEPARATOR;
    }

    /**
     * @return void
     */
    public function printInfo(): void
    {
        $this->printer->setFont();
        $this->printText->new($this->separator, false);
        $this->printer->setTextSize(2, 1);
        $this->printText->new($this->document->document_type);
        $this->printText->new($this->document->number_full);
        $this->printer->setTextSize(1, 1);
        $this->printText->new($this->separator, false);
    }
}
