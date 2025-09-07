<?php
namespace App\Controller\Feed\Baagl;

use App\Service\FeedProvider;
use App\Service\Baagl\BaaglImportXml;
use App\Service\Baagl\BaaglItemNormalizer;
use App\Service\Baagl\BaaglShoptetMatcher;
use App\Service\Baagl\BaaglShoptetWriter;
use App\Domain\ShoptetXml;
use App\Domain\FeedKind;
use App\Form\DefaultType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class UpdateController extends AbstractController
{
    public function __construct(private string $XmlFeedPath) {}
    
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
        $xml = $feeds->fetch(FeedKind::All);
        $items = $normalizer->normalize($xml->items,'update');
        $xmlShoptet = $feeds->fetch(FeedKind::Shoptet);

        // 1) Rozdělení        
        $m = $matcher->match($xmlShoptet,  $items->item);

        // // 2) Vytvoření chybějících
        // foreach ($m['missing'] as $item) {            
        //     $writer->add((array) $item, 'BAAGL');
        // }

        // 3) Update existujících
        foreach ($m['matched'] as $code => $pair) {
        $data[] = $writer->update((array) $pair['shopitem'], (array) $pair['item'], 'BAAGL');
        }

        $xml = (new ShoptetXml())->build($data);
        file_put_contents($this->XmlFeedPath, $xml);

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
