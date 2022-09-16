<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Middleware;

use Psr\Http\Message\{
    ServerRequestInterface as Request,
    ResponseInterface as Response,
    UploadedFileInterface,
};
use Psr\Http\Server\{MiddlewareInterface as Middleware, RequestHandlerInterface as RequestHandler};
use Slim\Exception\HttpBadRequestException;

use TryAgainLater\MediaConvertAppApi\Util\{FileUtils, MimeType};

class FileUploadOptions
{
    public function __construct(
        public readonly string $name,
        public readonly string $uploadDirectory,

        // -1 means there is no limit to size
        public readonly int $maxSize = -1,

        // null means any mime types are allowed
        public readonly ?array $allowedMimeTypes = null,
    ) {
    }
}

class WithFileUploadsMiddleware implements Middleware
{
    public function __construct(private FileUploadOptions $options)
    {
    }

    /** @inheritdoc */
    public function process(Request $request, RequestHandler $handler): Response
    {
        /** @var UploadedFileInterface[] */
        $uploadedFiles = $request->getUploadedFiles();

        // allow only a single file to be uploaded
        if (
            !isset($uploadedFiles[$this->options->name]) ||
            is_array($uploadedFiles[$this->options->name])
        ) {
            throw new HttpBadRequestException(
                $request,
                'You can only upload exactly one video at a time.',
            );
        }

        $uploadedFile = $uploadedFiles[$this->options->name];

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new HttpBadRequestException(
                $request,
                'Failed to upload the file.',
            );
        }

        $uploadedFileName = FileUtils::moveUploadedFile(
            directory: $this->options->uploadDirectory,
            uploadedFile: $uploadedFile,
        );
        $uploadedFilePath = $this->options->uploadDirectory . $uploadedFileName;

        if ($this->options->maxSize > 0 && filesize($uploadedFilePath) > $this->options->maxSize) {
            $maxSizeForHumans = FileUtils::bytesToHumanString($this->options->maxSize);
            throw new HttpBadRequestException(
                $request,
                "Max file upload size is {$maxSizeForHumans}.",
            );
        }

        $uploadedFileMimeType = FileUtils::getMimeType($uploadedFilePath);
        if (
            $this->options->allowedMimeTypes !== null &&
            !in_array(
                $uploadedFileMimeType,
                $this->options->allowedMimeTypes,
                strict: true,
            )
        ) {
            $allowedMimeTypes = implode(
                separator: ', ',
                array: array_map(
                    fn (MimeType $mimeType) => $mimeType->value,
                    $this->options->allowedMimeTypes,
                ),
            );

            throw new HttpBadRequestException(
                $request,
                "Invalid file type. These are the allowed MIME types: {$allowedMimeTypes}.",
            );
        }

        // in case the MIME type is correct but the extension on the client's file is messed up
        $correctedExtension = $uploadedFileMimeType->getExtension();
        $pathInfo = pathinfo($uploadedFilePath);
        if ($correctedExtension !== $pathInfo['extension']) {
            $correctedFilePath =
                ($pathInfo['dirname'] ? $pathInfo['dirname'] . DIRECTORY_SEPARATOR : '') .
                $pathInfo['filename'] .
                '.' .
                $correctedExtension;

            rename($uploadedFilePath, $correctedFilePath);
            $uploadedFilePath = $correctedFilePath;
        }

        $request = $request->withAttribute(
            "files.{$this->options->name}",
            [
                'path' => $uploadedFilePath,
                'mimeType' => $uploadedFileMimeType,
                'originalName' => $uploadedFile->getClientFilename(),
            ],
        );

        return $handler->handle($request);
    }
}
