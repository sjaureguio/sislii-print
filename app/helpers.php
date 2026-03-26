<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Extrae y decodifica el payload de una request.
 * Todos los datos llegan como base64 + gzip en el query param 'data'.
 *
 * @param Request $request
 * @return mixed
 */
function validateAndDecode(Request $request): mixed
{
    $raw = $request->getQueryParams()['data'] ?? null;

    if (!$raw) {
        throw new InvalidArgumentException('Missing data');
    }

    $decoded = base64_decode($raw, true);
    if ($decoded === false) {
        throw new InvalidArgumentException('Invalid base64');
    }

    $decompressed = gzinflate($decoded);
    if ($decompressed === false) {
        throw new InvalidArgumentException('Invalid gzip data');
    }

    $data = json_decode($decompressed);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new InvalidArgumentException('Invalid JSON');
    }

    return $data;
}

function closeTab(Response $response): Response
{
    $response->getBody()->write('<script>window.close()</script>');
    return $response;
}

function jsonResponse(Response $response, $data, int $status = 200): Response
{
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
}
