<?php

namespace App\Infrastructure\Handler\Printing\Shared;

use Mike42\Escpos\Printer;

class PrintText
{
    protected Printer $printer;

    public function __construct(Printer $printer)
    {
        $this->printer = $printer;
    }

    public function __invoke(string $text, bool $newLine = true): void
    {
         $this->new($text, $newLine);
    }

    /**
     * @param string $text
     * @param bool $newLine
     * @return void
     */
    public function new(string $text, bool $newLine = true): void
    {
        if (!$newLine) {
            $this->printer->text($text);
            return;
        }
        $this->printer->text("$text\n");
    }
}
