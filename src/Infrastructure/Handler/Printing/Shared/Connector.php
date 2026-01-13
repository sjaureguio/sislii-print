<?php

namespace App\Infrastructure\Handler\Printing\Shared;

use Exception;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class Connector
{
    protected object $printer;

    public function __construct(object $printer)
    {
        $this->printer = $printer;
    }

    /**
     * @return Printer
     * @throws Exception
     */
    public function getPrinter(): Printer
    {
        if ($this->printer->connection_type === 'ethernet') {
            $connector = new NetworkPrintConnector($this->printer->ip_address, 9100);
        } else {
            $connector = new WindowsPrintConnector($this->printer->name);
        }
        // $connector = new WindowsPrintConnector("POS-80C");

        return new Printer($connector);
    }
}
