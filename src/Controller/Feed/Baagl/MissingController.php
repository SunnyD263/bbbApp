<?php
namespace App\Controller\Feed\Baagl;

use App\Service\Db;
use App\Service\XmlFeedClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

final class MissingController extends AbstractController
{
    #[Route('/feed/baagl/missing', name: 'missing_baagl', methods: ['GET','POST'])]
    public function __invoke(XmlFeedClient $client): JsonResponse
    {
        $xml = $client->fetchSimpleXml('https://example.com/feed.xml', xsdPath: null);
        $items = [];
        foreach ($xml->channel->item ?? [] as $it) {
            $items[] = [
                'title' => (string) $it->title,
                'link'  => (string) $it->link,
                'date'  => (string) $it->pubDate,
            ];
        }
        return new JsonResponse($items);
    }
}
