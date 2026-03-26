<?php

namespace App\Infrastructure\Handler\Printing;

use App\Infrastructure\Handler\Printing\Shared\Connector;
use App\Infrastructure\Handler\Printing\Shared\PrintText;
use Exception;
use Mike42\Escpos\Printer;

class OrderHandler
{
    protected Printer $printer;
    protected string $separator;
    protected PrintText $text;

    public function __construct()
    {
         $this->separator = "------------------------------------------------\n";
    }

    public function printCommand(object $data): array
    {
        $prodArea = $data->area;

        try {
            $connector = new Connector($prodArea->printer);
            $this->getHeader($connector);
            $this->text->new('PEDIDO');
            $this->text->new('N° ' . $data->order_number);

            $this->printer->feed();

            if (isset($data->desk_name) && isset($data->salon_name)) {
                $this->text->new(join(' - ', [$data->salon_name, $data->desk_name]));
            }
            if ($data->sale_channel_id === '01') {
                $this->text->new('PARA LLEVAR');
            }
            if ($data->sale_channel_id === '03') {
                $this->text->new('DELIVERY');
            }
            $this->printGeneralData($data);
            if (isset($data->note)) {
                $this->text->new("NOTA: " . $data->note);
            }
            $this->text->new($this->separator, false);
            $this->printer->setDoubleStrike();
            $this->text->new("CANT. PRODUCTO                                  ");
            $this->printer->setDoubleStrike(false);
            $this->text->new($this->separator, false);

            $this->printer->setTextSize(2, 1);
            $this->printer -> setFont(Printer::FONT_B);

            foreach ($prodArea->items as $item) {
                $product = mb_substr($item->name, 0, 29);
                $product = $product . str_repeat(' ', 29 - mb_strlen($product));

                $quantity = str_pad((string) $item->quantity, 3);

                $this->text->new($quantity . $product, false);
                $text = '';
                if (isset($item->notes)) {
                    $text .= $item->notes;
                }
                if ($item->to_take_away && $data->sale_channel_id === '02') {
                    $text .= ' PARA LLEVAR';
                }
                if ($text !== '') {
                    $this->text->new($text);
                }
                $this->printer->setTextSize(1, 1);
                $this->printer->setFont();
                $this->text->new($this->separator);
                $this->printer->setFont(Printer::FONT_B);
                $this->printer->setTextSize(2, 1);
            }
            $this->printer->setTextSize(1, 1);
            $this->printer -> setFont();
            $this->text->new($this->separator, false);
            $this->printer->feed();
            $this->printer->cut();

            $this->printer->getPrintConnector()->write(PRINTER::ESC . "B" . chr(4) . chr(2));

            $this->printer->close();

            return [
                'success' => true,
                'message' => 'Pedido impreso con éxito.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function printPreAccount(object $data): array
    {
        $printer = $data->printer;
        $saleChannelId = $data->sale_channel_id;

        try {
            $connector = new Connector($printer);
            $this->getHeader($connector);
            if ($saleChannelId === '03') {
                $this->text->new('TICKET DE ENVÍO');
                $this->text->new('N° ' . $data->delivery->number);
            } else {
                $this->text->new('PRE-CUENTA');
                $this->text->new('N° ' . $data->order_number);
            }

            $this->printer->feed();

            if (isset($data->desk_name) && isset($data->salon_name)) {
                $this->text->new(join(' - ', [$data->salon_name, $data->desk_name]));
            }

            $this->printGeneralData($data);

            $this->text->new($this->separator, false);
            $this->printer->setDoubleStrike();
            $this->text->new('CANT. PRODUCTO                      P.U. IMPORTE');
            $this->printer->setDoubleStrike(false);
            $this->text->new($this->separator, false);

            foreach ($data->items as $item) {
                $product = mb_substr($item->name, 0, 28);
                $product = $product . str_repeat(' ', 28 - mb_strlen($product));

                $quantity = str_pad((string)$item->quantity, 6);
                $unitPrice = str_pad(number_format($item->unit_price, 2), 6, ' ', STR_PAD_LEFT);
                $totalFmt = number_format($item->total, 2);
                $total = str_pad($totalFmt, 8, ' ', STR_PAD_LEFT);

                $this->text->new($quantity . $product . $unitPrice . $total);
                if (isset($item->notes)) {
                    $this->text->new($item->notes);
                }
                if ($item->discount && $item->discount->amount > 0) {
                    $this->text->new("      Descto: " . number_format($item->discount->amount, 2));
                }
                if ($item->is_courtesy) {
                    $this->text->new("      Cortesía");
                }
            }

            $this->text->new($this->separator);
            $this->printer->setJustification(Printer::JUSTIFY_RIGHT);
            $this->printer->setDoubleStrike();
            $this->printer->setTextSize(2, 1);
            $totalFmt = number_format($data->total, 2);
            $totalOrder = str_pad($totalFmt, 12, ' ', STR_PAD_LEFT);
            $this->text->new("Total S/ $totalOrder");

            $this->printer->feed();
            $this->printer->cut();
            $this->printer->close();

            return [
                'success' => true,
                'message' => 'Pre-Cuenta impresa con éxito.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param Connector $connector
     * @return void
     * @throws Exception
     */
    public function getHeader(Connector $connector): void
    {
        $this->printer = $connector->getPrinter();
        $this->printer->initialize();

        $this->text = new PrintText($this->printer);

        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->setFont();
        $this->text->new($this->separator, false);
        $this->printer->setTextSize(2, 1);
    }

    /**
     * @param object $data
     * @return void
     */
    public function printGeneralData(object $data): void
    {
        $saleChannelId = $data->sale_channel_id;
        $this->printer->setTextSize(1, 1);
        $this->text->new($this->separator, false);
        $this->printer->feed();

        $this->printer->setJustification();
        $this->text->new("FECHA: " . $data->date);

        if (isset($data->user_name)) {
            $this->text->new("MOZO: " . $data->user_name);
        }

        if ($saleChannelId === '03' && isset($data->delivery)) {
            $delivery = $data->delivery;
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->text->new("DATOS DE ENVÍO");
            $this->printer->setJustification();
            if (isset($delivery->recipient_name)) {
                $this->text->new("CLIENTE: " . $delivery->recipient_name);
            }
            if (isset($delivery->recipient_phone)) {
                $this->text->new("TELÉFONO: " . $data->delivery->recipient_phone);
            }
            $this->text->new("DIRECCIÓN: " . $data->delivery->recipient_address);

            if (isset($delivery->payment_method)) {
                foreach ($delivery->payment_method as $payMethod) {
                    $this->text->new("M. PAGO: " . $payMethod->name);
                }
            }

            if (isset($delivery->note)) {
                $this->text->new("NOTAS: " . $data->delivery->note);
            }
        }
    }
}
