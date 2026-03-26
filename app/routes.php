<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Infrastructure\Handler\Printing\CashHandler;
use App\Infrastructure\Handler\Printing\OrderHandler;
use App\Infrastructure\Handler\Printing\ExpenseHandler;
use App\Infrastructure\Handler\Printing\InvoiceHandler;
use App\Infrastructure\Handler\Printing\PrintSaleReceiptHandler;
use App\Infrastructure\Handler\Printing\SaleNoteHandler;
use App\Infrastructure\Handler\Printing\PrintHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

require_once __DIR__ . '/helpers.php';

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {

        $data = [
            'success' => true,
            'message' => 'OK'
        ];

        $response->getBody()->write(json_encode($data));
        $response->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

        return $response;
    });

    $app->group('/print', function (Group $group) {
        $group->get('/orders', function (Request $request, Response $response) {
            $data = validateAndDecode($request);
            $prt = new OrderHandler();
            foreach ($data->areas as $area) {
                $data->area = $area;
                $prt->printCommand($data);
            }
            return closeTab($response);
        });


        $group->get('/pre-accounts', function (Request $request, Response $response) {
            $data = validateAndDecode($request);
            $prt = new OrderHandler();
            $prt->printPreAccount($data);
            return closeTab($response);
        });

        $group->get('/invoices', function (Request $request, Response $response) {
            $data = validateAndDecode($request);
            $prt = new InvoiceHandler();
            $prt->printInvoice($data);
            return closeTab($response);
        });

        $group->post('/receipt', function (Request $request, Response $response) {
            $data = validateAndDecode($request);
            $prt = new PrintSaleReceiptHandler();
            return jsonResponse($response, $prt->printRceipt($data));
        });

        $group->post('/sale-note', function (Request $request, Response $response) {
            $data = validateAndDecode($request);
            $prt = new SaleNoteHandler();
            return jsonResponse($response, $prt->printSaleNote($data));
        });

        $group->get('/final-balance', function (Request $request, Response $response) {
            $data = validateAndDecode($request);
            $prt = new CashHandler();
            $prt->printFinalBalance($data);
            return closeTab($response);
        });

        $group->post('/cancell-order', function (Request $request, Response $response) {
            $data = validateAndDecode($request);
            $prt = new PrintHandler();
            return jsonResponse($response, $prt->printCancelOrder($data));
        });

        $group->post('/expense', function (Request $request, Response $response) {
            $data = validateAndDecode($request);
            $prt = new ExpenseHandler();
            return jsonResponse($response, $prt->printExpense($data));
        });
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });
};
