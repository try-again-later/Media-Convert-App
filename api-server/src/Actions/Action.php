<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Actions;

use Psr\Http\Message\{ResponseInterface as Response, ServerRequestInterface as Request};
use Slim\Exception\{HttpNotFoundException, HttpBadRequestException};

abstract class Action
{
    protected Request $request;
    protected Response $response;
    protected array $args;

    public function __construct()
    {
    }

    /**
     * @throws HttpNotFoundException
     * @throws HttpBadRequestException
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        return $this->action();
    }

    /**
     * @throws HttpNotFoundException
     * @throws HttpBadRequestException
     */
    abstract protected function action(): Response;

    /**
     * @throws HttpBadRequestException
     */
    protected function resolveArg(string $name): mixed
    {
        if (!isset($this->args[$name])) {
            throw new HttpBadRequestException(
                $this->request,
                "Could not resolve argument '{$name}'.",
            );
        }

        return $this->args[$name];
    }

    protected function respond(ActionPayload $payload): Response
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT);
        $this->response->getBody()->write($json);

        return $this->response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($payload->getStatusCode());
    }

    protected function respondWithData(
        array | object | null $data = null,
        $statusCode = 200,
    ): Response {
        $payload = new ActionPayload(
            statusCode: $statusCode,
            data: $data,
        );

        return $this->respond($payload);
    }
}
