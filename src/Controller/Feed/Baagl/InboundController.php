<?php

namespace App\Controller\Feed\Baagl;

use App\Entity\Baagl\BaaglInbound;
use App\Form\Baagl\InboundUploadType;
use App\Service\Baagl\BaaglInboundTable;
use App\Service\Baagl\BaaglInboundParser;
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
        BaaglInboundParser $parser,
        BaaglInboundTable $importer,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): Response {

        // formulář
        $form = $this->createForm(InboundUploadType::class);
        $form->handleRequest($request);

        $result = null;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /** @var UploadedFile|null $file */
                $file = $form->get('html_file')->getData();
                if (!$file) {
                    $this->addFlash('error', 'Soubor nebyl nahrán.');
                } else {
                    try {
                                
                        if ($request->isMethod('GET')) {

                        //***\src\Service\Baagl\BaaglInboundTable.php***
                            $importer->rebuild(); // TRUNCATE/DELETE
                        }
                        $html   = file_get_contents($file->getPathname());

                        //***\src\Service\Baagl\BaaglInboundParser.php***
                        $result = $parser->parseHtml($html);

                         //***\src\Service\Baagl\BaaglInboundTable.php***
                        $inserted = $importer->insertFromItems($result['items'] ?? []);

                        $this->addFlash('success', sprintf(
                            'Načtení položek objednávky. Vloženo %d položek. Množství: %d, suma s DPH: %s',
                            $inserted,
                            (int)($result['sumQty'] ?? 0),
                            number_format((float)($result['sumPrice'] ?? 0), 2, ',', ' ')
                        ));

                        $this->addFlash('baagl_result', $result);

                        return $this->redirectToRoute('inbound_baagl');
                    } catch (\Throwable $e) {
                        $this->addFlash('error', 'Zpracování selhalo: '.$e->getMessage());
                    }
                }
            } else {
                foreach ($form->getErrors(true, true) as $err) {
                    $this->addFlash('error', $err->getMessage());
                }
            }
        }

        $flashResult = $request->getSession()->getFlashBag()->get('baagl_result');
        if (!empty($flashResult)) {
            $result = $flashResult[0];
        }

        // JSON do konzole
        $resultJson = $serializer->serialize($result, 'json', ['groups' => ['debug']]);

        return $this->render('feed/baagl/inbound.html.twig', [
            'form'       => $form->createView(),
            'result'     => $result,
            'resultJson' => $resultJson,
        ]);
    }
}
