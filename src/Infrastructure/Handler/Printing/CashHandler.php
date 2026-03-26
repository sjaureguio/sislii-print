<?php

namespace App\Infrastructure\Handler\Printing;

use App\Domain\Printing\Printing;
use App\Infrastructure\Handler\Printing\Contracts\BalanceSectionPrinterInterface;
use App\Infrastructure\Handler\Printing\Sections\ExpenseSection;
use App\Infrastructure\Handler\Printing\Sections\FinalSummarySection;
use App\Infrastructure\Handler\Printing\Sections\HeaderSection;
use App\Infrastructure\Handler\Printing\Sections\IncomeSection;
use App\Infrastructure\Handler\Printing\Sections\ProductsSoldSection;
use App\Infrastructure\Handler\Printing\Shared\Connector;
use App\Infrastructure\Handler\Printing\Shared\PrintText;
use Exception;

/**
 * Orquestador del cuadre de caja.
 *
 * SRP : solo coordina el flujo de impresión; cada sección tiene su propia clase.
 * OCP : agregar/quitar secciones no requiere modificar este archivo.
 * DIP : depende de BalanceSectionPrinterInterface, no de implementaciones concretas.
 */
class CashHandler
{
    private const string SEPARATOR_48 = "================================================\n";

    /** @var BalanceSectionPrinterInterface[] */
    private array $sections;

    public function __construct()
    {
        // Las secciones se construyen en el orden en que deben imprimirse.
        // Para agregar una nueva sección basta con añadirla aquí (OCP).
        $this->sections = [
            new HeaderSection(self::SEPARATOR_48),
            new FinalSummarySection(self::SEPARATOR_48),
            new IncomeSection(self::SEPARATOR_48),
            new ProductsSoldSection(self::SEPARATOR_48),
            new ExpenseSection(self::SEPARATOR_48),
        ];
    }

    /**
     * Punto de entrada único: inicializa la impresora, delega cada sección
     * y finaliza con corte de papel.
     */
    public function printFinalBalance(object $data): array
    {
        try {
            $connector = new Connector($data->printer);
            $printer   = $connector->getPrinter();
            $printer->initialize();

            $text = new PrintText($printer);

            foreach ($this->sections as $section) {
                $section->print($printer, $text, $data);
            }

            $printer->feed(3);
            $printer->cut();
            $printer->close();

            return [
                'success' => true,
                'message' => 'Cuadre final de caja impreso con éxito',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
