<?php

namespace App\Infrastructure\Handler\Printing\Shared;

use Mike42\Escpos\Printer;

class PrintInvoiceTotal
{
    private Printer $printer;
    private object $document;
    private PrintText $printText;

    public function __construct(Printer $printer, object $document)
    {
        $this->printer = $printer;
        $this->document = $document;
        $this->printText = new PrintText($this->printer);
    }

    /**
     * @param string|null $taxRegimeId
     * @param string $docTypeId
     * @return void
     */
    public function printTotals(string|null $taxRegimeId, string $docTypeId): void
    {
        $this->printer->setJustification(Printer::JUSTIFY_RIGHT);
        if ($taxRegimeId !== null && $taxRegimeId !== '01' && $docTypeId !== '80') {
            if ($this->document->total_discount > 0) {
                $fmtTotalDiscount = number_format(floatval($this->document->total_discount), 2);
                $totalDiscount = str_pad($fmtTotalDiscount, 12, ' ', STR_PAD_LEFT);
                $this->printText->new("DSCTO. TOTAL: {$this->document->currency} $totalDiscount");
            }

            if ($this->document->total_exportation > 0) {
                $fmtTotalExportation = number_format(floatval($this->document->total_exportation), 2);
                $totalExportation = str_pad($fmtTotalExportation, 12, ' ', STR_PAD_LEFT);
                $this->printText->new("OP. EXPORTACIÓN: {$this->document->currency} $totalExportation");
            }

            if ($this->document->total_free > 0) {
                $fmtTotalFree = number_format(floatval($this->document->total_free), 2);
                $totalFree = str_pad($fmtTotalFree, 12, ' ', STR_PAD_LEFT);
                $this->printText->new("OP. GRATUITAS: {$this->document->currency} $totalFree");
            }

            if ($this->document->total_unaffected > 0) {
                $fmtTotalUnaffected = number_format(floatval($this->document->total_unaffected), 2);
                $totalUnaffected = str_pad($fmtTotalUnaffected, 12, ' ', STR_PAD_LEFT);
                $this->printText->new("OP. INAFECTAS: {$this->document->currency} $totalUnaffected");
            }

            if ($this->document->total_exonerated > 0) {
                $fmtTotalExonerated = number_format(floatval($this->document->total_exonerated), 2);
                $totalExonerated = str_pad($fmtTotalExonerated, 12, ' ', STR_PAD_LEFT);
                $this->printText->new("OP. EXONERADAS: S/ $totalExonerated");
            }

            if ($this->document->total_taxed > 0) {
                $fmtTotalTaxed = number_format(floatval($this->document->total_taxed), 2);
                $totalTaxed = str_pad($fmtTotalTaxed, 12, ' ', STR_PAD_LEFT);
                $this->printText->new("OP. GRAVADAS: {$this->document->currency} $totalTaxed");
            }

            if ($this->document->total_plastic_bag_taxes > 0) {
                $fmtTotalPlastic = number_format(floatval($this->document->total_plastic_bag_taxes), 2);
                $total_plastic_bag_taxes = str_pad($fmtTotalPlastic, 12, ' ', STR_PAD_LEFT);
                $this->printText->new("ICBPER: {$this->document->currency} $total_plastic_bag_taxes");
            }

            if ($this->document->total_igv > 0) {
                $fmtTotalIgv = number_format(floatval($this->document->total_igv), 2);
                $total_igv = str_pad($fmtTotalIgv, 12, ' ', STR_PAD_LEFT);
                $this->printText->new("IGV: {$this->document->currency} $total_igv");
            }
        }

        $fmtTotal = number_format(floatval($this->document->total), 2);
        $total = str_pad($fmtTotal, 12, ' ', STR_PAD_LEFT);

        $this->printer->setDoubleStrike();
        $this->printText->new("TOTAL: {$this->document->currency} $total");
    }
}
