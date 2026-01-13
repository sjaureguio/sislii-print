<?php

declare(strict_types=1);

namespace App\Infrastructure\Handler\Printing;

use App\Domain\Printing\Printing;
use App\Infrastructure\Handler\Printing\Shared\Connector;
use App\Infrastructure\Handler\Printing\Shared\PrintCompany;
use App\Infrastructure\Handler\Printing\Shared\PrintText;
use Mike42\Escpos\Printer;
use Throwable;

class ExpenseHandler
{
    protected string $separator;
    protected Printer $printer;


    public function __construct()
    {
    }

    public function printExpense($data): array
    {
        $company = $data->company;
        // $establishment = $data->establishment;
        $document = $data->document;
        $user = $data->user;
        $config = $data->config;

        $this->separator = $config->print_of_format === 'ticket_58'
            ? Printing::SEPARATOR42
            : Printing::SEPARATOR;

        try {
            $connector = new Connector($config);
            $this->printer = $connector->getPrinter();
            $this->printer->initialize();

            $this->printer->pulse();

            $printText = new PrintText($this->printer);

            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $printCompany = new PrintCompany($this->printer, $company);
            $printCompany->printLogo();
            $printCompany->printInfo();

            $this->printer->setFont(Printer::FONT_B);
            $printText($this->separator, false);
            $this->printer->setTextSize(2, 1);
            $printText('TICKET DE ' . $document->type_desc);
            $printText('TE01-' . $document->id);
            $this->printer->setTextSize(1, 1);
            $printText($this->separator, false);

            $this->printer->setTextSize(2, 1);
            $this->printer->setJustification();
            $printText("FECHA: $document->date_of_trx");
            $printText("OPERACIÓN: $document->operation");
            $printText('USUARIO: ' . $user->name);


            $this->printer->feed();
            $this->printer->setFont(Printer::FONT_B);
            $currency = $document->currency_type_id === 'PEN' ? 'S/' : '$';

            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $printText("MONTO");
            $this->printer->setTextSize(2, 2);
            $printText("$currency $document->amount");

            $this->printer->feed();
            $this->printer->setJustification();
            $this->printer->setTextSize(1, 1);
            $this->printer->setDoubleStrike();
            if (isset($document->reference)) {
                $printText("DESCRIPCION: $document->reference");
            }

            $this->printer->feed(2);
            $this->printer->cut();
            $this->printer->close();

            return [
                'success' => true,
                'message' => 'Impresión exitosa',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al imprimir',
                'error' => $e->getMessage(),
            ];
        }
    }
}
