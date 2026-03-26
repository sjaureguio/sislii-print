<?php

namespace App\Infrastructure\Handler\Printing;

use App\Domain\Printing\Printing;
use App\Infrastructure\Handler\Printing\Shared\Connector;
use App\Infrastructure\Handler\Printing\Shared\PrintCredit;
use App\Infrastructure\Handler\Printing\Shared\PrintHeader;
use App\Infrastructure\Handler\Printing\Shared\PrintInvoiceHeader;
use App\Infrastructure\Handler\Printing\Shared\PrintInvoiceItems;
use App\Infrastructure\Handler\Printing\Shared\PrintInvoiceTotal;
use App\Infrastructure\Handler\Printing\Shared\PrintText;
use Exception;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\Printer;
use Mpdf\QrCode\QrCode;
use Mpdf\QrCode\Output;
use Mpdf\QrCode\QrCodeException;
use Throwable;

class InvoiceHandler
{
    protected Printer $printer;

    public function printInvoice(object $data): array
    {
        $company = $data->company;
        $establishment = $data->establishment;
        $document = $data->document;
        $documentBase = $data->document_base;
        $customer = $document->customer;
        $user = $document->user;
        $printer = $data->printer;

        try {
            $connector = new Connector($printer);
            $this->printer = $connector->getPrinter();
            $this->printer->initialize();

            if ($printer->open_cash_drawer) {
                $this->printer->pulse();
            }

            $printText = new PrintText($this->printer);

            $printHeader = new PrintHeader(
                $this->printer,
                $company,
                $establishment,
                $document,
                $customer,
                true,
            );
            $printHeader();

            $printInvoiceHeader = new PrintInvoiceHeader(
                $this->printer,
                $document,
                $documentBase,
                $user,
            );
            $printInvoiceHeader->printInfo();

            $printItems = new PrintInvoiceItems(
                $this->printer,
                $document->items,
            );
            $printItems->printItems();

            $printTotal = new PrintInvoiceTotal(
                $this->printer,
                $document
            );
            $printTotal->printTotals($company->tax_regime_id, $document->document_type_id);

            $this->printInvoiceFooter(
                $printText,
                $document,
            );

            $this->printer->feed();
            $this->printer->cut();
            $this->printer->close();

            return [
                'success' => true,
                'message' => 'Comprobante de pago impreso con éxito'
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

    /**
     * @param PrintText $printText
     * @param object $document
     * @return void
     * @throws QrCodeException
     * @throws Exception
     */
    private function printInvoiceFooter(PrintText $printText, object $document): void
    {
        $separator = Printing::SEPARATOR;
        $this->printer->setJustification(0);
        $this->printer->setDoubleStrike(false);
        $printText->new($separator);

        if ($document->document_type_id !== '80') {
            foreach ($document->legends as $row) {
                if ($row->code === 1000) {
                    $printText('SON: ' . $row->value);
                } else {
                    $printText($row->value);
                }
            }

            if (isset($document->additional_information)) {
                $printText("OBSERVACIONES: $document->additional_information");
            }

            $printText($separator);
            if ($document->payment_condition_type_id === '02') {
                $printCredit = new PrintCredit($this->printer, $document);
                $printCredit->printCreditInfo();
            }

            $this->printer->setJustification(Printer::JUSTIFY_CENTER);

            $printText->new("REPRESENTACION IMPRESA DE LA " . $document->document_type);
            $printText->new("ESTA PUEDE SER CONSULTADA EN:");
            $printText->new($document->url);

            $this->generateQrPng($document->qr, $document->filename);

            $directory = __DIR__ . '/../../../../public/images';
            $filename = $directory . "/$document->filename.png";

            $image = EscposImage::load($filename, false);
            $this->printer->bitImage($image);

            $printText->new($document->hash);
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        }

        $printText->new($separator, false);
        $printText->new('¡GRACIAS POR SU COMPRA - REGRESA PRONTO!');
    }

    /**
     * @throws QrCodeException
     */
    public function generateQrPng($text, $filename): void
    {
        $qrCode = new QrCode($text, 'L');
        $output = new Output\Png();

        $directory = __DIR__ . '/../../../../public/images';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($directory . DIRECTORY_SEPARATOR . $filename . '.png', $output->output($qrCode, 170));
    }
}
