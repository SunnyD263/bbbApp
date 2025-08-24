<?php
namespace App\Controller\Feed\Baagl;

use App\Service\Db;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class InboundController extends AbstractController
{
    #[Route('/Feed/Baagl/inbound', name: 'inbound_index', methods: ['GET'])]
    public function index(Request $r, Db $db): Response
    {
        $q = trim((string)$r->query->get('q',''));
        $sql = 'SELECT * FROM baagl_inbound';
        $par = [];
        if ($q !== '') { $sql .= ' WHERE artikl LIKE :q OR ean LIKE :q OR name LIKE :q'; $par['q'] = '%'.$q.'%'; }
        $sql .= ' ORDER BY id DESC LIMIT 200';
        $rows = $db->select($sql, $par);
        return $this->render('Feed/Baagl/inbound.html.twig', ['rows'=>$rows, 'q'=>$q]);
    }

    #[Route('/shoptet/missing', name: 'inbound_missing', methods: ['GET'])]
    public function missing(Db $db): Response
    {
        $rows = $db->select(
          'SELECT i.*
             FROM baagl_inbound i
             LEFT JOIN category c ON c.source = i.category_src
            WHERE (c.id IS NULL) OR (COALESCE(i.pc_czk_vat,0)=0 AND COALESCE(i.pc_eur,0)=0)
            ORDER BY i.id DESC'
        );
        return $this->render('Feed/Baagl/missing.html.twig', ['rows'=>$rows]);
    }
}
