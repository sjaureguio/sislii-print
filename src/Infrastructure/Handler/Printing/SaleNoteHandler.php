<?php

namespace App\Infrastructure\Handler\Printing;

use App\Domain\Printing\Printing;
use App\Infrastructure\Handler\Printing\Shared\Connector;
use App\Infrastructure\Handler\Printing\Shared\Payment;
use App\Infrastructure\Handler\Printing\Shared\PrintCredit;
use App\Infrastructure\Handler\Printing\Shared\PrintDate;
use App\Infrastructure\Handler\Printing\Shared\PrintHeader;
use App\Infrastructure\Handler\Printing\Shared\PrintInvoiceItems;
use App\Infrastructure\Handler\Printing\Shared\PrintText;
use App\Infrastructure\Handler\Printing\Shared\PrintUser;
use Exception;
use Mike42\Escpos\Printer;
use Throwable;

class SaleNoteHandler
{
    protected Printer $printer;

    public function printSaleNote($data): array
    {
        $company = $data->company;
        $establishment = $data->establishment;
        $document = $data->document;
        $customer = $document->customer;
        $user = $document->user;
        $config = $data->config;

        try {
            $connector = new Connector($config);
            $this->printer = $connector->getPrinter();
            $this->printer->initialize();

            // Active for open cash drawer
            // $printer->pulse();

            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $printText = new PrintText($this->printer);

            $printHeader = new PrintHeader(
                $this->printer,
                $company,
                $establishment,
                $document,
                $customer,
                $data->from_web,
                $config->print_of_format
            );
            $printHeader();

            $printDate = new PrintDate($this->printer, $document->date_of_issue, $document->time_of_issue, $printText);
            $printDate();

            $printUser = new PrintUser($this->printer, $user, $printText);
            $printUser();

            $printItems = new PrintInvoiceItems(
                $this->printer,
                $document->items,
                $config->print_of_format
            );
            $printItems->printItems();

            $this->printTotal($document, $printText);

            $this->printFooter($document, $printText, $config->print_of_format);

            $this->printer->feed();
            $this->printer->cut();
            $this->printer->close();

            return [
                'success' => true,
                'message' => 'Documento impreso con éxito'
            ];
        } catch (Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    private function printTotal($document, $printText): void
    {
        $this->printer->setFont();
        $this->printer->setJustification(Printer::JUSTIFY_RIGHT);
        $this->printer->setDoubleStrike();

        $fmtTotal = number_format(floatval($document->total), 2);
        $total = str_pad($fmtTotal, 12, ' ', STR_PAD_LEFT);
        $printText->new("TOTAL: S/ $total");
    }

    /**
     * @throws Exception
     */
    private function printFooter($document, $printText, string $format): void
    {
        $this->printer->setFont(Printer::FONT_B);
        $this->printer->feed();
        $this->printer->setJustification();

        if (isset($document->additional_information)) {
            $this->printer->text("OBSERVACIONES: $document->additional_information");
        }

        $this->printer->text($format === 'ticket_58' ? Printing::SEPARATOR42 : Printing::SEPARATOR);
        if (in_array($document->payment_condition_type_id, ['2', '3'])) {
            $printCredit = new PrintCredit($this->printer, $document, $format);
            $printCredit->printCreditInfo();
        } else {
            $printPayments = new Payment($this->printer, $document, $format);
            $printPayments->printPayments();
        }

        $this->printer->setFont(Printer::FONT_B);
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);

        $this->printer->feed(2);
        $printText->new('CANJEAR POR BOLETA O FACTURA');
        $printText->new('GRACIAS POR SU COMPRA, VUELVA PRONTO');
    }
}
