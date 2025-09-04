<?php
namespace App\Controller\Feed\Baagl;

use App\Service\FeedProvider;
use App\Service\Baagl\BaaglImportXml;
use App\Service\Baagl\BaaglItemNormalizer;
use App\Service\Baagl\BaaglShoptetMatcher;
use App\Service\Baagl\BaaglShoptetWriter;
use App\Domain\FeedKind;
use App\Form\DefaultType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/feed/baagl/import', name: 'import_baagl', methods: ['GET','POST'])]
final class ImportController extends AbstractController
{

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

        // 2) Update existujících
        foreach ($m['matched'] as $code => $pair) {
            $writer->updateShopitem($pair['shopitem'], $pair['item'], $company);
        }

        // 3) Vytvoření chybějících
        $promo = fn(string $company, string $nazev, string $catName) => get_promo($company, $nazev, $catName);
        foreach ($m['missing'] as $row) {
            $writer->buildShopitem($xmlShoptet, $row, $company, $promo);
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
