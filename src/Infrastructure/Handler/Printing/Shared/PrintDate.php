<?php

namespace App\Infrastructure\Handler\Printing\Shared;

use Mike42\Escpos\Printer;

class PrintDate
{
    protected Printer $printer;
    protected string $dateOfIssue;
    protected string $timeOfIssue;
    protected PrintText $printText;

    public function __construct(
        Printer $printer,
        string $dateOfIssue,
        string $timeOfIssue,
        PrintText $printText
    ) {
        $this->printer = $printer;
        $this->dateOfIssue = $dateOfIssue;
        $this->timeOfIssue = $timeOfIssue;
        $this->printText = $printText;
    }

    public function __invoke(): void
    {
        $this->printText->new("Fecha y Hora: $this->dateOfIssue $this->timeOfIssue");
    }
}
