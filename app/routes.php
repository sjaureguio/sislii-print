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
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Infrastructure\Handler\Printing\PrintHandler;

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

    $app->post('/print/order', function (Request $request, Response $response) {
        $data = json_decode(
            json_encode($request->getParsedBody())
        );

        $prt = new OrderHandler();
        $payload = $prt->printCommand($data);

        $response->getBody()->write(json_encode($payload));
        return $response;
    });

    $app->post('/print/pre-account', function (Request $request, Response $response) {
        $data = json_decode(
            json_encode($request->getParsedBody())
        );

        $prt = new OrderHandler();
        $payload = $prt->printPreAccount($data);
        $response->getBody()->write(json_encode($payload));

        return $response;
    });

    $app->post('/print/bill', function (Request $request, Response $response) {
        $data = json_decode(
            json_encode($request->getParsedBody())
        );

        $prt = new InvoiceHandler();
        $payload = $prt->printInvoice($data);
        $response->getBody()->write(json_encode($payload));
        $response->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

        return $response;
    });

    $app->post('/print/receipt', function (Request $request, Response $response) {

        $prt = new PrintSaleReceiptHandler();
        $payload = $prt->printRceipt(json_decode(file_get_contents('php://input'), false));
        $response->getBody()->write(json_encode($payload));
        $response->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

        return $response;
    });

    $app->post('/print/sale-note', function (Request $request, Response $response) {

        $prt = new SaleNoteHandler();
        $payload = $prt->printSaleNote(json_decode(file_get_contents('php://input'), false));
        $response->getBody()->write(json_encode($payload));

        return $response;
    });

    $app->post('/print/final-balance', function (Request $request, Response $response) {

        $prt = new CashHandler();
        $payload = $prt->printFinalBalance(json_decode(file_get_contents('php://input'), false));
        $response->getBody()->write(json_encode($payload));

        return $response;
    });

    $app->post('/print/cancell-order', function (Request $request, Response $response) {

        $prt = new PrintHandler();
        $payload = $prt->printCancelOrder(json_decode(file_get_contents('php://input'), false));
        $response->getBody()->write(json_encode($payload));

        return $response;
    });

    $app->post('/print/expense', function (Request $request, Response $response) {

        $prt = new ExpenseHandler();
        $payload = $prt->printExpense(json_decode(file_get_contents('php://input'), false));
        $response->getBody()->write(json_encode($payload));

        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });
};
