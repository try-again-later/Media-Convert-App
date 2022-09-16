<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Application;

use PDO;
use Throwable;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\{
    HttpException,
    HttpNotFoundException,
    HttpMethodNotAllowedException,
    HttpUnauthorizedException,
    HttpForbiddenException,
    HttpBadRequestException,
    HttpNotImplementedException,
};
use Slim\Handlers\ErrorHandler as SlimErrorHandler;

use TryAgainLater\MediaConvertAppApi\Actions\{ActionError, ActionPayload};

class HttpErrorHandler extends SlimErrorHandler
{
    protected function respond(): Response
    {
        $statusCode = 500;
        $error = new ActionError(
            type: ActionError::SERVER_ERROR,
            description: 'Internal server error.',
        );

        $exception = $this->exception;
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getCode();
            $error->setDescription($exception->getMessage());

            if ($exception instanceof HttpNotFoundException) {
                $error->setType(ActionError::RESOURCE_NOT_FOUND);
            } elseif ($exception instanceof HttpMethodNotAllowedException) {
                $error->setType(ActionError::NOT_ALLOWED);
            } elseif ($exception instanceof HttpUnauthorizedException) {
                $error->setType(ActionError::UNAUTHENTICATED);
            } elseif ($exception instanceof HttpForbiddenException) {
                $error->setType(ActionError::INSUFFICIENT_PRIVILEGES);
            } elseif ($exception instanceof HttpBadRequestException) {
                $error->setType(ActionError::BAD_REQUEST);
            } elseif ($exception instanceof HttpNotImplementedException) {
                $error->setType(ActionError::NOT_IMPLEMENTED);
            }
        } else if ($exception instanceof Throwable && $this->displayErrorDetails) {
            $error->setDescription($exception->getMessage());
        }

        $payload = new ActionPayload(
            statusCode: $statusCode,
            error: $error,
        );
        $encodedPayload = json_encode($payload, JSON_PRETTY_PRINT);

        $response = $this->responseFactory->createResponse($statusCode);
        $response->getBody()->write($encodedPayload);

        return $response->withHeader('Content-Type', 'application/json');
    }
}
