<?php

namespace App\Controller\Feed\Activa;

use App\Service\FeedProvider;
use App\Domain\FeedKind;
use App\Domain\ShoptetXml;
use App\Entity\Activa\ActivaInbound;
use App\Form\InboundUploadType;
use App\Service\Activa\ActivaShoptetMatcher;
use App\Service\Activa\ActivaShoptetWriter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class InboundController extends AbstractController
{
    public function __construct(private string $xmlExportFeedPath) {}

    #[Route('/feed/activa/inbound', name: 'inbound_activa', methods: ['GET','POST'])]
    public function inboundBaagl(
        Request $request,
        FeedProvider $feeds,
        EntityManagerInterface $em,
        ActivaShoptetMatcher $matcher,
        SerializerInterface $serializer,
        ActivaShoptetWriter $writer,
    ): Response {

        // formulář
        $session = $request->getSession();
        $form = $this->createForm(InboundUploadType::class);
        $form->handleRequest($request);

        $action = $request->request->get('action');

        //***\src\Service\Baagl\BaaglInboundTable.php***
        // if ($request->isMethod('GET')) { $importer->rebuild(); } 

        if ($action === 'load' && $form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $file */
            $file = $form->get('html_file')->getData();

            if (!$file instanceof UploadedFile) {
                $this->addFlash('danger', 'Soubor nebyl nahrán.');
                return $this->render('feed/baagl/inbound.html.twig', [
                    'form'      => $form->createView(),
                    'result'    => $result,
                    'resultJson'=> $result ? json_encode($result, JSON_UNESCAPED_UNICODE) : null,
                ]);
            }

            // Získej obsah HTML pro parser
            $html = file_get_contents($file->getPathname());
                                
            //***\src\Service\Baagl\BaaglInboundParser.php***
            $parsed = $parser->parseHtml($html);

            //***\src\Service\Baagl\BaaglInboundTable.php***
            // $inserted = $importer->insertFromItems($parsed['items'] ?? []);

            $session->set('inbound_result', $parsed);
            $session->set('inbound_rows_json', json_encode($parsed['items'] ?? [], JSON_UNESCAPED_UNICODE));
            $result   = $parsed;
            $rowsJson = json_encode($parsed['items'] ?? [], JSON_UNESCAPED_UNICODE);

        }

        // B) Generovat (zpracuj poslední načtený výsledek)
        if ($action === 'generate') {
            
            // CSRF kontrola pro „generate“ tlačítko
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('inbound_generate', $token)) {
                $this->addFlash('danger', 'Neplatný CSRF token.');
                return $this->redirectToRoute('inbound_baagl');
            }

            $sessionData = $session->get('inbound_result');
            if (!$sessionData || empty($sessionData['items'])) {
                $this->addFlash('warning', 'Není co generovat. Nejprve načti data.');
                return $this->redirectToRoute('inbound_baagl');
            }

            $xmlShoptet = $feeds->fetch(FeedKind::Shoptet);
            $m = $matcher->match($xmlShoptet, $sessionData["items"]);

            // 2) Vytvoření chybějících
            foreach ($m['missing'] as $item) {            
                $this->logger?->info(sprintf(
                    'Kategorie nenalezena: "%s" (Regnum: %s). Nebude naskladněna.',
                    $m["shopitem"]->NAME, $m["shopitem"]->CODE
                ));
            }

            // 3) Update existujících
            foreach ($m['matched'] as $code => $pair) {
            $data[] = $writer->inbound((array) $pair['shopitem'], (array) $pair['item'], 'Výchozí sklad');
            }

            $xml = (new ShoptetXml())->build($data);
            file_put_contents($this->xmlExportFeedPath, $xml);

            $this->addFlash('success',
                'Načtení položek objednávky. Vloženo %d položek.',
                'chyba'
            );

            return $this->redirectToRoute('inbound_baagl');
        }

        return $this->render('feed/baagl/inbound.html.twig', [
            'form'       => $form->createView(),
            'result'     => $result,
            'resultJson' => $result ? json_encode($result, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }
}
