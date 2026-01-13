<?php

declare(strict_types=1);

namespace App\Infrastructure\Handler\Printing;

use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Exception;

class PrintHandler
{
    protected string $separator;
    protected string $separator48;
    protected Printer $printer;

    public function __construct()
    {
        $this->separator = "----------------------------------------------------------------\n";
        $this->separator48 = "------------------------------------------------\n";
    }

    public function printCancelOrder($request): array
    {
        $data = json_decode($request, true);

        // $zone = $data['zone'];
        $order = $data['order'];

        try {
            // $connector = new NetworkPrintConnector("{$zone['printer_ip']}", 9100);
            $connector = new WindowsPrintConnector("POS-80C");
            $printer = new Printer($connector);

            $printer -> initialize();
            $printer -> setJustification(Printer::JUSTIFY_CENTER);

            $printer -> setFont();
            $printer -> setDoubleStrike();
            $printer -> text("------------------------------------------------\n");
            $printer -> setTextSize(2, 1);
            $printer -> text("CANCELACIÓN DE PEDIDO \n");
            $printer -> setTextSize(1, 1);
            $printer -> text("------------------------------------------------\n");
            $printer -> feed();


            $printer -> setJustification(Printer::JUSTIFY_CENTER);

            $printer -> text("El pedido N° {$order['identifier']} ha sido concelado \n");


            $printer -> feed();


            if ($order['table']['name']) {
                $printer -> text($order['table']['name'] . "\n");
            }
            if ($order['seller']['name']) {
                $printer -> text("MOZ@: " . $order['seller']['name'] . "\n");
            }

            $printer -> setFont(Printer::FONT_B);
            $printer -> setDoubleStrike(false);

            $printer -> text("----------------------------------------------------------------\n");
            $printer -> setDoubleStrike();
            $printer -> text("CANT. DESCRIPCION                                P.U.    IMPORTE\n");
            $printer -> setDoubleStrike(false);
            $printer -> text("----------------------------------------------------------------\n");

            for ($i = 0, $n = count($order['items']); $i < $n; $i++) {
                $item = $order['items'][$i];

                $item_name = substr($item['item'], 0, 39);

                $quantity = str_pad((string)$item['quantity'], 6);
                $producto = str_pad($item_name, 39);
                $precio_unit = str_pad($item['unit_price'], 8, " ", STR_PAD_LEFT);
                $total = str_pad(number_format($item['total'], 2, '.', ''), 11, " ", STR_PAD_LEFT);

                $printer -> text($quantity . $producto . $precio_unit . $total . "\n");
            }

            $printer -> text("----------------------------------------------------------------\n");

            $printer -> feed();
            $printer -> cut();
            $printer -> close();

            return [
                'success' => true,
                'message' => 'El pedido ha sido cancelado con exito.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param string $text
     * @param bool $newLine
     * @return void
     */
    private function printText(string $text, bool $newLine = true): void
    {
        if (!$newLine) {
            $this->printer->text($text);
            return;
        }
        $this->printer->text("$text\n");
    }
}
