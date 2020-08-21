<?php

namespace Symfony\Bundle\FrameworkBundle;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;

final class Responder
{
    private $urlGenerator;
    private $twig;
    private $serializer;

    public function __construct(UrlGeneratorInterface $urlGenerator, Environment $twig = null, SerializerInterface $serializer = null)
    {
        $this->urlGenerator = $urlGenerator;
        $this->twig = $twig;
        $this->serializer = $serializer;
    }

    /**
     * Returns a JsonResponse that uses the serializer component if enabled, or json_encode.
     */
    public function json($data, int $status = Response::HTTP_OK, array $headers = [], array $context = []): JsonResponse
    {
        if (null !== $this->serializer) {
            $json = $this->serializer->serialize($data, JsonEncoder::FORMAT, array_merge([
                'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
            ], $context));

            return new JsonResponse($json, $status, $headers, true);
        }

        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Returns a RedirectResponse to the given URL.
     */
    public function redirect(string $url, int $status = Response::HTTP_FOUND): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     */
    public function route(string $route, array $parameters = [], int $status = Response::HTTP_FOUND): RedirectResponse
    {
        return $this->redirect($this->urlGenerator->generate($route, $parameters), $status);
    }

    public function render(string $view, array $parameters = []): Response
    {
        if (null === $this->twig) {
            throw new \LogicException('You can not use the "render" method if the Twig Bundle is not available. Try running "composer require symfony/twig-bundle".');
        }

        $content = $this->twig->render($view, $parameters);

        return new Response($content);
    }

    /**
     * Returns an empty response with the given status code.
     */
    public function empty(int $status): Response
    {
        return new Response(null, $status);
    }

    /**
     * Returns a BinaryFileResponse object with original or customized file name and disposition header.
     *
     * @param string $file path to file to be sent as response
     */
    protected function file(string $file, string $filename = null, string $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT): BinaryFileResponse
    {
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition($disposition, $filename ?? $response->getFile()->getFilename());

        return $response;
    }

    /**
     * Streams a view.
     */
    protected function stream(string $view, array $parameters = []): StreamedResponse
    {
        if (null === $this->twig) {
            throw new \LogicException('You can not use the "render" method if the Twig Bundle is not available. Try running "composer require symfony/twig-bundle".');
        }

        $callback = function () use ($view, $parameters): void {
            $this->twig->display($view, $parameters);
        };

        return new StreamedResponse($callback);
    }
}
