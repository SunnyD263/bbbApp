<?php
namespace App\Controller\Feed\Baagl;

use App\Service\Db;
use App\Service\XmlFeedClient;
use App\Service\FeedProvider;
use App\Domain\FeedKind;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

final class MissingController extends AbstractController
{
    #[Route('/feed/baagl/missing', name: 'missing_baagl', methods: ['GET','POST'])]
    public function __invoke(FeedProvider $feeds): Response
    {
        $xml = $feeds->fetch(FeedKind::All);
        // ... tvoje logika
        return new Response('OK');
    }
}
