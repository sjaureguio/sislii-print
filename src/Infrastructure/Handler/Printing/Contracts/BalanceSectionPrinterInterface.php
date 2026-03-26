<?php

namespace App\Infrastructure\Handler\Printing\Contracts;

use App\Infrastructure\Handler\Printing\Shared\PrintText;
use Mike42\Escpos\Printer;

/**
 * ISP + DIP: cada sección del cuadre de caja implementa este contrato.
 * Permite agregar nuevas secciones sin modificar el orquestador (OCP).
 */
interface BalanceSectionPrinterInterface
{
    public function print(Printer $printer, PrintText $text, object $data): void;
}
