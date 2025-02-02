<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class RequestResponseLoggerListener
{
    public function __construct(private LoggerInterface $logger) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $this->logger->info('Incoming request', [
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'headers' => $request->headers->all(),
            'body' => $request->getContent(),
        ]);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $this->logger->info('Outgoing response', [
            'status_code' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
            'body' => $response->getContent(),
        ]);
    }
}
