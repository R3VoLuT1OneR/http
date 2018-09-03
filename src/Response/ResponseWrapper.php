<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Response;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Spiral\Files\FilesInterface;
use Spiral\Files\Streams\StreamableInterface;
use Spiral\Http\Exceptions\ResponseException;
use Spiral\Http\Traits\JsonTrait;
use Zend\Diactoros\Stream;

/**
 * Provides ability to write content into currently active (resolved using container) response.
 */
class ResponseWrapper
{
    use JsonTrait;

    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory = null;

    /**
     * @var FilesInterface
     */
    protected $files = null;

    /**
     * @param ResponseFactoryInterface $responseFactory
     * @param FilesInterface           $files
     */
    public function __construct(ResponseFactoryInterface $responseFactory, FilesInterface $files)
    {
        $this->responseFactory = $responseFactory;
        $this->files = $files;
    }

    /**
     * Mount redirect headers into response
     *
     * @param UriInterface|string $uri
     * @param int                 $code
     * @return ResponseInterface
     *
     * @throws ResponseException
     */
    public function redirect($uri, int $code = 302): ResponseInterface
    {
        if (!is_string($uri) && !$uri instanceof UriInterface) {
            throw new ResponseException("Redirect allowed only for string or UriInterface uris");
        }

        return $this->responseFactory->createResponse($code)->withHeader("Location", (string)$uri);
    }

    /**
     * Write json data into response.
     *
     * @param mixed $data
     * @param int   $code
     *
     * @return ResponseInterface
     */
    public function json($data, int $code = 200): ResponseInterface
    {
        return $this->writeJson($this->responseFactory->createResponse($code), $data, $code);
    }

    /**
     * Configure response to send given attachment to client.
     *
     * @param string|StreamInterface|StreamableInterface $filename Local filename or stream or streamable or resource.
     * @param string                                     $name     Public file name (in attachment), by default local
     *                                                             filename. Name is mandratory when filename supplied
     *                                                             in a form of stream or resource.
     * @param string                                     $mimetype
     *
     * @return ResponseInterface
     *
     * @throws ResponseException
     */
    public function attachment(
        $filename,
        string $name = '',
        string $mimetype = 'application/octet-stream'
    ): ResponseInterface {

        if (empty($name)) {
            if (!is_string($filename)) {
                throw new ResponseException("Unable to resolve public filename");
            }

            $name = basename($filename);
        }

        $stream = $this->getStream($filename);

        /**
         * PSR7 love to return 'self' from methods, IDE thinks now that response is MessageInterface
         *
         * @var ResponseInterface $response
         */
        $response = $this->responseFactory;

        $response = $response->withHeader('Content-Type', $mimetype);
        $response = $response->withHeader('Content-Length', (string)$stream->getSize());
        $response = $response->withHeader(
            'Content-Disposition',
            'attachment; filename="' . addcslashes($name, '"') . '"'
        );

        return $response->withBody($stream);
    }

    /**
     * Write html content into response and set content-type header.
     *
     * @param string $body
     *
     * @return ResponseInterface
     */
    public function html(string $body): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($body);

        return $response->withHeader('Content-type', 'text/html; charset=UTF-8');
    }

    /**
     * Create stream for given filename.
     *
     * @param string|StreamInterface|StreamableInterface $filename
     *
     * @return StreamInterface
     */
    private function getStream($filename): StreamInterface
    {
        if ($filename instanceof StreamableInterface) {
            return $filename->getStream();
        }

        if ($filename instanceof StreamInterface) {
            return $filename;
        }

        if (is_resource($filename)) {
            return new Stream($filename, 'r');
        }

        if (!$this->files->isFile($filename)) {
            throw new ResponseException("Unable to allocate response body stream, file does not exist");
        }

        return new Stream(fopen($this->files->localFilename($filename), 'r'));
    }
}
