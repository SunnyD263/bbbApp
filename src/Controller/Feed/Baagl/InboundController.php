<?php

namespace App\Controller\Feed\Baagl;

use App\Service\FeedProvider;
use App\Domain\FeedKind;
use App\Entity\Baagl\BaaglInbound;
use App\Form\Baagl\InboundUploadType;
use App\Service\Baagl\BaaglInboundTable;
use App\Service\Baagl\BaaglInboundParser;
use App\Service\Baagl\BaaglShoptetMatcher;
use App\Service\Baagl\BaaglShoptetWriter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class InboundController extends AbstractController
{
    #[Route('/feed/baagl/inbound', name: 'inbound_baagl', methods: ['GET','POST'])]
    public function inboundBaagl(
        Request $request,
        FeedProvider $feeds,
        BaaglInboundParser $parser,
        BaaglInboundTable $importer,
        EntityManagerInterface $em,
        BaaglShoptetMatcher $matcher,
        SerializerInterface $serializer,
        BaaglShoptetWriter $writer,
    ): Response {

        // formulář
        $session = $request->getSession();
        $form = $this->createForm(InboundUploadType::class);
        $form->handleRequest($request);

        $result = null;

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

            $data = $session->get('inbound_result');
            if (!$data || empty($data['items'])) {
                $this->addFlash('warning', 'Není co generovat. Nejprve načti data.');
                return $this->redirectToRoute('inbound_baagl');
            }

            $xmlShoptet = $feeds->fetch(FeedKind::Shoptet);
            $m = $matcher->match($xmlShoptet, $data["items"] ?? $items);

            // 2) Vytvoření chybějících
            foreach ($m['missing'] as $item) {            
                $writer->add((array) $item, 'Výchozí sklad');
            }

            // 3) Update existujících
            foreach ($m['matched'] as $code => $pair) {
                $writer->inbound((array) $pair['shopitem'], (array) $pair['item'], 'Výchozí sklad');
            }

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
