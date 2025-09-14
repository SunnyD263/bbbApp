<?php
namespace App\Controller\Inventura;

use App\Service\Db;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ItemSelectController extends AbstractController
{
    #[Route('/inventura/vyber/{ean}', name: 'inventory_item_select', methods: ['GET'])]
    public function __invoke(string $ean, Db $db): Response
    {
        $rows = $db->select(
            'SELECT id, name, ean, productNumber, barva, velikost
             FROM inventura WHERE ean = :ean ORDER BY velikost', ['ean'=>$ean]
        );
        return $this->render('inventura/item_select.html.twig', ['ean'=>$ean, 'rows'=>$rows]);
    }
}
