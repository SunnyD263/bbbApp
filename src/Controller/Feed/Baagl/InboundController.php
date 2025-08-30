<?php

namespace App\Controller\Feed\Baagl;

use App\Entity\Baagl\BaaglInbound;
use App\Form\Baagl\InboundUploadType;
use App\Service\Baagl\BaaglInboundImporter;
use App\Service\Baagl\BaaglInboundParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class InboundController extends AbstractController
{
    #[Route('/feed/baagl/inbound', name: 'inbound_shoptet', methods: ['GET','POST'])]
    public function shoptet(
        Request $request,
        BaaglInboundParser $parser,
        BaaglInboundImporter $importer,
        EntityManagerInterface $em
    ): Response {
        $q = trim((string) $request->query->get('q', ''));

        // seznam pro tabulku
        $repo = $em->getRepository(BaaglInbound::class);
        $qb = $repo->createQueryBuilder('b')->orderBy('b.id','asc')->setMaxResults(1000);
        if ($q !== '') {
            $qb->andWhere('b.code LIKE :q OR b.nazev LIKE :q')->setParameter('q', "%{$q}%");
        }
        $rows = $qb->getQuery()->getResult();

        // formulář
        $form = $this->createForm(InboundUploadType::class);
        $form->handleRequest($request);

        $result = null;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
                $file = $form->get('html_file')->getData();
                if (!$file) {
                    $this->addFlash('error', 'Soubor nebyl nahrán.');
                } else {
                    try {
                        $html   = file_get_contents($file->getPathname());
                        $result = $parser->parseHtml($html);

                        $inserted = $importer->rebuildAndInsertFromItems($result['items'] ?? []);

                        $this->addFlash('success', sprintf(
                            'Tabulka obnovena (DROP/CREATE), vloženo %d položek. Množství: %d, suma s DPH: %s',
                            $inserted,
                            (int)($result['sumQty'] ?? 0),
                            number_format((float)($result['sumPrice'] ?? 0), 2, ',', ' ')
                        ));

                        return $this->redirectToRoute('inbound_shoptet');
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

        return $this->render('feed/baagl/inbound.html.twig', [
            'q'      => $q,
            'rows'   => $rows,
            'form'   => $form->createView(),
            'result' => $result,
        ]);
    }
}
