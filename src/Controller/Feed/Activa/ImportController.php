<?php
namespace App\Controller\Feed\Activa;

use App\Service\FeedProvider;
use App\Service\Activa\ActivaItemNormalizer;
use App\Service\Activa\ActivaShoptetMatcher;
use App\Service\Activa\ActivaShoptetWriter;
use App\Domain\ShoptetXml;
use App\Domain\FeedKind;
use App\Form\DefaultType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


final class ImportController extends AbstractController
{
    public function __construct(private string $xmlExportFeedPath) {}

    #[Route('/feed/activa/import', name: 'import_activa', methods: ['GET','POST'])]
    public function __invoke(
        Request $request, 
        FeedProvider $feeds,
        ActivaItemNormalizer $normalizer,
        ActivaShoptetMatcher $matcher,
        ActivaShoptetWriter $writer,
    ): Response {
        $form = $this->createForm(DefaultType::class);
        $form->handleRequest($request);

        $xml = $feeds->fetch(FeedKind::ActivaAll);
        $items = $normalizer->normalize($xml,'import');
        $xmlShoptet = $feeds->fetch(FeedKind::Shoptet);

        // 1) Rozdělení        
        $m = $matcher->match($xmlShoptet,$items->SHOPITEM);
        // 2) Vytvoření chybějících
        foreach ($m['missing'] as $item) {            
        $data[] = $writer->add((array)$item, 'ACTIVA');
        }

        $xml = (new ShoptetXml())->build($data);
        file_put_contents($this->xmlExportFeedPath, $xml);

        return $this->render('feed/default.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
