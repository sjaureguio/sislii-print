<?php

namespace App\Infrastructure\Handler\Printing;

use App\Infrastructure\Handler\Printing\Shared\Connector;
use App\Infrastructure\Handler\Printing\Shared\PrintCompany;
use App\Infrastructure\Handler\Printing\Shared\PrintText;
use Mike42\Escpos\Printer;
use Throwable;

class PrintSaleReceiptHandler
{
    protected string $separator;
    protected string $separator48;
    protected Printer $printer;

    public function __construct()
    {
        $this->separator = "----------------------------------------------------------------\n";
        $this->separator48 = "------------------------------------------------\n";
    }

    public function printRceipt($data): array
    {
        $company = $data->company;
        $document = $data->document;
        $config = $data->config;

        try {
            $connector = new Connector($config);
            $this->printer = $connector->getPrinter();
            $this->printer->initialize();

            $this->printer->pulse();

            $printText = new PrintText($this->printer);

            $printCompany = new PrintCompany($this->printer, $company);
            $printCompany->printLogo();

            $printText->new($this->separator48, false);

            $printText($document->number_full);

            $printText->new($this->separator48);

            $this->printer->feed();

            $this->printer->setTextSize(2, 2);

            $fmtTotal = number_format(floatval($document->total), 2);
            $printText("$document->currency $fmtTotal");

            $this->printer->feed();
            $this->printer->cut();
            $this->printer->close();

            return [
                'success' => true,
                'message' => 'Ticket de venta impreso con éxito'
            ];
        } catch (Throwable $th) {
            if (isset($this->printer)) {
                $this->printer->close();
            }
            return [
                'success' => false,
                'message' => $th->getMessage()
            ];
        }
    }
}
