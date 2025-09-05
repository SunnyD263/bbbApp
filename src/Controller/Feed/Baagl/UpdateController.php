<?php
namespace App\Controller\Feed\Baagl;

use App\Service\Db;
use App\Service\XmlFeedClient;
use App\Service\FeedProvider;
use App\Domain\FeedKind;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

final class UpdateController extends AbstractController
{
    #[Route('/feed/baagl/update', name: 'update_baagl', methods: ['GET'])]
    public function __invoke(
        BaaglImportXml $importer,
        Request $request, 
        FeedProvider $feeds,
        BaaglItemNormalizer $normalizer,
        BaaglShoptetMatcher $matcher,
        BaaglShoptetWriter $writer,
    ): Response {
        $form = $this->createForm(DefaultType::class);
        $form->handleRequest($request);

        $result = null;
        $xml = $feeds->fetch(FeedKind::Instock);
        $items = $normalizer->normalize($xml->items);
        $xmlShoptet = $feeds->fetch(FeedKind::Shoptet);

        // 1) Rozdělení        
        $m = $matcher->match($xmlShoptet, $items['item'] ?? $items);

        // // 2) Vytvoření chybějících
        // foreach ($m['missing'] as $item) {            
        //     $writer->add((array) $item, 'BAAGL');
        // }

        // 3) Update existujících
        foreach ($m['matched'] as $code => $pair) {
            $writer->update((array) $pair['shopitem'], (array) $pair['item'], 'BAAGL');
        }



        // $importer->rebuild();
        // $inserted = $importer->insertFromItems((array)$items ?? []);
        // $this->addFlash('success', sprintf(
        //     'Bylo naimportováno %d položek',
        //     $inserted
        // )); 

        // $this->addFlash('baagl_result', $result);

        //$result = sprintf('Načteno položek: %d', $count);

        return $this->render('feed/baagl/default.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
