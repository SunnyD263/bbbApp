<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

final class DevExceptionConsoleSubscriber implements EventSubscriberInterface
{
    private const REQ_ATTR = '_last_throwable_for_console';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onException', 0],
            KernelEvents::RESPONSE  => ['onResponse', 0],
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        // ulož výjimku do requestu; zpracujeme ji až při RESPONSE
        $event->getRequest()->attributes->set(self::REQ_ATTR, $event->getThrowable());
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $t = $request->attributes->get(self::REQ_ATTR);
        if (!$t instanceof \Throwable) {
            return; // nic k injektování
        }

        $response = $event->getResponse();
        if (!$this->isHtmlResponse($response)) {
            return; // nechceme kazit JSON, binárky apod.
        }

        $payload = [
            'message' => $t->getMessage(),
            'type'    => get_class($t),
            'file'    => $t->getFile(),
            'line'    => $t->getLine(),
            // opatrně: stacktrace může být dlouhý – stačí pár řádků
            'trace'   => array_slice(explode("\n", $t->getTraceAsString()), 0, 20),
        ];

        $js = '<script>(function(){try{'
            .'console.groupCollapsed("%cPHP exception%c %s","background:#c00;color:#fff;padding:2px 6px;border-radius:4px","color:#c00","'.htmlspecialchars(addslashes($payload['message']), ENT_QUOTES).'");
              console.error('.json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).');
              console.groupEnd();
            }catch(e){/*no-op*/}})();</script>';

        $content = $response->getContent() ?? '';
        // vlož těsně před </body>, nebo na konec, když body chybí
        if (stripos($content, '</body>') !== false) {
            $content = preg_replace('~</body>~i', $js.'</body>', $content, 1);
        } else {
            $content .= $js;
        }
        $response->setContent($content);
    }

    private function isHtmlResponse(Response $response): bool
    {
        $ct = $response->headers->get('Content-Type') ?? '';
        return str_starts_with(strtolower($ct), 'text/html');
    }
}
