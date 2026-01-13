<?php

namespace App\Infrastructure\Handler\Printing\Shared;

use Mike42\Escpos\Printer;

class PrintUser
{
    protected Printer $printer;
    protected object $user;
    protected PrintText $printText;

    public function __construct(
        Printer $printer,
        object $user,
        PrintText $printText
    ) {
        $this->printer = $printer;
        $this->user = $user;
        $this->printText = $printText;
    }

    public function __invoke(): void
    {
        $this->printText->new("USUARIO: {$this->user->name}");
    }
}
