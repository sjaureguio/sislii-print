<?php

namespace App\Infrastructure\Handler\Printing\Shared;

use Mike42\Escpos\Printer;

class PrintInvoiceHeader
{
    protected Printer $printer;
    protected object $document;
    protected mixed $docBase;
    protected string $user;
    protected PrintText $printText;

    public function __construct(Printer $printer, object $document, mixed $docBase, string $user)
    {
        $this->printer = $printer;
        $this->document = $document;
        $this->docBase = $docBase;
        $this->user = $user;
        $this->printText = new  PrintText($this->printer);
    }

    /**
     * @return void
     */
    public function printInfo(): void
    {
        $this->printText->new("FECHA DE EMISIÓN: {$this->document->date_of_issue} {$this->document->time_of_issue}");

        $this->printText->new("CONDICIÓN DE PAGO: {$this->document->payment_condition_type}");

        $this->printText->new("USUARIO: $this->user");

        if (isset($this->docBase)) {
            $docAffected = $this->docBase->affected_document;
            $fullNumber = join('-', [$docAffected->series, $docAffected->number]);
            $this->printText->new('DOC. AFECTADO: ' . $fullNumber);
            $noteType = ($this->docBase->note_type === 'credit')
                ? $this->docBase->note_credit_type->description
                : $this->docBase->note_debit_type->description;

            $this->printText->new('TIPO DE NOTA: ' . $noteType);
            $this->printText->new('DESCRIPCION: ' . $this->docBase->note_description);
        }
    }
}
