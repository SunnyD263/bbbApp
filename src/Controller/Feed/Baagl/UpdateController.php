<?php
namespace App\Controller\Feed\Baagl;

use App\Service\Db;
use App\Service\XmlFeedClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

final class UpdateController extends AbstractController
{
    #[Route('/feed/baagl/update', name: 'update_baagl', methods: ['GET'])]
    public function updateBaagl(
        Request $request,
        BaaglInboundParser $parser,
        BaaglInboundImporter $importer,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): Response {

    }

}
