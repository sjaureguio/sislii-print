<?php

namespace App\Infrastructure\Handler\Printing\Shared;

use Exception;
use Mike42\Escpos\Printer;

class PrintHeader
{
    protected PrintText $printText;

    public function __construct(
        protected Printer $printer,
        protected object $company,
        protected object $establishment,
        protected object $document,
        protected object $customer,
        protected bool $fromWeb,
    ) {
        $this->printText = new PrintText($this->printer);
    }

    /**
     * @throws Exception
     */
    public function __invoke(): void
    {
        $printCompany = new PrintCompany($this->printer, $this->company);
        $printCompany->printLogo();
        $printCompany->printInfo();

        $printEst = new PrintEstablishment($this->printer, $this->establishment);
        $printEst->printInfo();

        $prinInvoiceType = new PrintInvoiceType($this->printer, $this->document);
        $prinInvoiceType->printInfo();

        $printCustomer = new PrintCustomer($this->printer, $this->customer, $this->fromWeb);
        $printCustomer->printInfo();
    }
}
