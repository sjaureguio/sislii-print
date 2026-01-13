<?php

namespace App\Infrastructure\Handler\Printing;

use App\Infrastructure\Handler\Printing\Shared\Connector;
use App\Infrastructure\Handler\Printing\Shared\PrintText;
use Exception;
use Mike42\Escpos\Printer;

class CashHandler
{
    protected string $separator;
    protected string $separator48;
    protected Printer $printer;

    public function __construct()
    {
        $this->separator = "----------------------------------------------------------------\n";
        $this->separator48 = "------------------------------------------------\n";
    }

    public function printFinalBalance($data): array
    {
        $config = $data->config;
        $opening = $data->opening;
        $items = $data->items;
        $payments = $data->payments;
        $transactions = $data->transactions;

        try {
            $connector = new Connector($config);
            $this->printer = $connector->getPrinter();
            $this->printer->initialize();

            $text = new PrintText($this->printer);

            $this->printer->setFont();
            $this->printer->setDoubleStrike();
            $text($this->separator48);
            $this->printer->setTextSize(2, 1);
            $text('CUADRE DE CAJA');
            $this->printer->setTextSize(1, 1);
            $text($this->separator48);
            $this->printer->feed();

            $this->printer->setDoubleStrike(false);
            $this->printer->setJustification();

            $text('Caja: ' . str_pad($opening->cash->name, 22, " ", STR_PAD_LEFT));

            $timestamp = strtotime($opening->date_opening);
            $dateOpening = date('d-m-Y', $timestamp);
            $text('FECHA APERT.: ' . str_pad($dateOpening, 10, ' ', STR_PAD_LEFT));
            $this->printer->feed();

            $this->printer->setDoubleStrike();
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);

            $text('VENTA DE PRODUCTO');

            $this->printer->setDoubleStrike();
            $this->printer->setFont(Printer::FONT_B);

            $this->printer->setJustification();
            $text($this->separator);
            $text('N°  CANT. PRODUCTO                                         TOTAL');
            $text($this->separator);
            $this->printer->setDoubleStrike(false);

            foreach ($items as $key => $item) {
                $counter = str_pad((string)++$key, 4);
                $quantity = str_pad($item->quantity, 6);
                $product = str_pad($item->name, 44);
                $fmtAmount = number_format(floatval($item->total), 2);
                $amount = str_pad($fmtAmount, 10, ' ', STR_PAD_LEFT);

                $text($counter . $quantity . $product . $amount);
            }

            $this->printer->feed();
            $this->printer->setDoubleStrike();
            $this->printer->setFont();
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $text('METODOS DE PAGO');
            $this->printer->setFont(Printer::FONT_B);

            $text($this->separator);
            $text('N°        M. PAGO                                         MONTO');
            $text($this->separator);
            $this->printer->setDoubleStrike(false);

            foreach ($payments as $key => $pay) {
                $counter = str_pad((string)++$key, 10);
                $description = str_pad($pay->description, 38);
                $fmtAmount = number_format(floatval($pay->total), 2);
                $amount = str_pad('S/ ' . $fmtAmount, 16, ' ', STR_PAD_LEFT);

                $text($counter . $description . $amount);
                $text($this->separator);
            }

            $this->printer->feed();
            $this->printer->setDoubleStrike();
            $this->printer->setFont();
            $text('OTROS MOVIMIENTOS');
            $this->printer->setFont(Printer::FONT_B);

            $this->printer->text($this->separator);
            $text('N°   TIPO           FECHA Y HORA                           MONTO');
            $text($this->separator);
            $this->printer->setDoubleStrike(false);

            foreach ($transactions as $key => $trx) {
                $counter = str_pad((string)++$key, 5);
                $type = str_pad($trx->type == 'income' ? 'INGRESO' : 'EGRESO', 15);

                $timestamp = strtotime($trx->date_of_trx);
                $date_fmt = str_pad(date('d-m-Y H:i a', $timestamp), 25);

                $fmtAmount = number_format(floatval($trx->amount), 2);
                $amount = str_pad('S/ ' . $fmtAmount, 19, ' ', STR_PAD_LEFT);

                $text($counter . $type . $date_fmt . $amount);
                $text('................................................................');
            }

            $this->printer->feed();
            $this->printer->setDoubleStrike();
            $this->printer->setFont();
            $text('SALDOS FINALES');
            $text($this->separator);
            $this->printer->setFont(Printer::FONT_B);

            $this->printer->setJustification(Printer::JUSTIFY_RIGHT);
            $fmtBeginningBalance = number_format(floatval($opening->beginning_balance), 2);
            $beginningBalance = str_pad($fmtBeginningBalance, 15, ' ', STR_PAD_LEFT);
            $text('SALDO INICIAL: S/ ' . $beginningBalance);

            $fmtIncome = number_format(floatval($opening->income), 2);
            $income = str_pad($fmtIncome, 15, ' ', STR_PAD_LEFT);
            $text('TOTAL INGRESOS: S/ ' . $income);

            $fmtExpense = number_format(floatval($opening->expense), 2);
            $expense = str_pad($fmtExpense, 15, ' ', STR_PAD_LEFT);
            $text('TOTAL EGRESOS: S/ ' . $expense);

            $fmtFinalBalance = number_format(floatval($opening->final_balance), 2);
            $finalBalance = str_pad($fmtFinalBalance, 15, ' ', STR_PAD_LEFT);
            $text('SALDO FINAL: S/ ' . $finalBalance);

            $this->printer->setFont(Printer::FONT_B);
            $this->printer->text($this->separator);

            $this->printer->feed();
            $this->printer->cut();
            $this->printer->close();

            return [
                'success' => true,
                'message' => 'Cuadre final de caja impreso con exito'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
