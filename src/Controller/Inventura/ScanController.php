<?php
namespace App\Controller\Inventura;

use App\Service\Db;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ScanController extends AbstractController
{
    #[Route('/inventura/scan', name: 'inventory_scan', methods: ['GET'])]
    public function index(Db $db): Response
    {
        $rows = $db->select('SELECT * FROM scan ORDER BY datum DESC, id DESC');
        return $this->render('inventura/scan.html.twig', ['rows' => $rows]);
    }

    #[Route('/inventura/scan/add-ean', name: 'inventory_scan_add_ean', methods: ['POST'])]
    public function addByEan(Request $req, Db $db): Response
    {
        $ean = trim((string)$req->request->get('ean',''));
        if (!preg_match('/^\d{6,18}$/', $ean)) {
            $this->addFlash('error', 'Špatný formát EAN.');
            return $this->redirectToRoute('inventory_scan');
        }

        $variants = $db->select(
            'SELECT id, name, ean, productNumber, barva, velikost
             FROM inventura WHERE ean = :ean ORDER BY velikost',
            ['ean' => $ean]
        );

        if (!$variants) {
            // Unknown
            $db->insertRow('scan', [
                'name' => 'Unknown',
                'ean'  => $ean,
                'productNumber' => 'Unknown',
                'barva' => 'Unknown',
                'velikost' => 0,
                'stock' => 1,
                'datum' => date('Y-m-d H:i:s'),
            ]);
            $this->addFlash('warning', 'EAN nebyl v inventuře nalezen. Přidán jako Unknown.');
            return $this->redirectToRoute('inventory_scan');
        }

        if (count($variants) === 1) {
            $r = $variants[0];
            $db->insertRow('scan', [
                'name' => $r['name'],
                'ean'  => $r['ean'],
                'productNumber' => $r['productNumber'],
                'barva' => $r['barva'],
                'velikost' => (int)$r['velikost'],
                'stock' => 1,
                'datum' => date('Y-m-d H:i:s'),
            ]);
            $this->addFlash('success', 'Záznam přidán.');
            return $this->redirectToRoute('inventory_scan');
        }

        // více variant → výběr
        return $this->redirectToRoute('inventory_item_select', ['ean' => $ean]);
    }

    #[Route('/inventura/scan/add-id', name: 'inventory_scan_add_id', methods: ['POST'])]
    public function addById(Request $req, Db $db): Response
    {
        $id = (int)$req->request->get('id', 0);
        if ($id <= 0) { return $this->redirectToRoute('inventory_scan'); }

        $r = $db->select('SELECT name, ean, productNumber, barva, velikost FROM inventura WHERE id = :id', ['id'=>$id])[0] ?? null;
        if (!$r) { $this->addFlash('error','ID nenalezeno.'); return $this->redirectToRoute('inventory_scan'); }

        $db->insertRow('scan', [
            'name' => $r['name'],
            'ean'  => $r['ean'],
            'productNumber' => $r['productNumber'],
            'barva' => $r['barva'],
            'velikost' => (int)$r['velikost'],
            'stock' => 1,
            'datum' => date('Y-m-d H:i:s'),
        ]);
        $this->addFlash('success', 'Záznam přidán.');
        return $this->redirectToRoute('inventory_scan');
    }

    #[Route('/inventura/scan/delete/{id}', name: 'inventory_scan_delete', methods: ['POST'])]
    public function deleteOne(int $id, Db $db): Response
    {
        $db->execute('DELETE FROM scan WHERE id = :id', ['id'=>$id]);
        return $this->redirectToRoute('inventory_scan');
    }

    #[Route('/inventura/scan/delete-all', name: 'inventory_scan_delete_all', methods: ['POST'])]
    public function deleteAll(Db $db): Response
    {
        $db->execute('DELETE FROM scan');
        return $this->redirectToRoute('inventory_scan');
    }
}
