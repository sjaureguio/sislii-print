<?php

namespace App\Infrastructure\Handler\Printing\Shared;

use App\Domain\Printing\Printing;
use Mike42\Escpos\Printer;

class PrintInvoiceItems
{
    private PrintText $printText;
    protected string $separator;

    public function __construct(
        protected Printer $printer,
        protected array $items,
    ) {
        $this->printText = new PrintText($this->printer);
        $this->separator = Printing::SEPARATOR;
    }

    /**
     * @return void
     */
    public function printItems(): void
    {
        $this->printText->new($this->separator, false);
        $this->printer->setDoubleStrike();
        $this->printText->new('CANT. PRODUCTO                      P.U. IMPORTE');
        $this->printer->setDoubleStrike(false);
        $this->printText->new($this->separator, false);

        for ($i = 0, $n = count($this->items); $i < $n; $i++) {
            $item = $this->items[$i];

            $itemDesc = $item->item->name;
            $quantity = str_pad((string)$item->quantity, 5);

            $itemName = substr($itemDesc, 0, 28);
            $product = str_pad($itemName, 28);

            $unitPrice = str_pad($item->unit_price, 6, ' ', STR_PAD_LEFT);

            $fmtTotal = number_format(floatval($item->total), 2);
            $itemTotal = str_pad($fmtTotal, 8, ' ', STR_PAD_LEFT);

            $this->printText->new($quantity . $product . $unitPrice . $itemTotal);
        }

        $this->printText->new($this->separator, false);
    }
}
