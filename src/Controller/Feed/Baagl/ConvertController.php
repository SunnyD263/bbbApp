<?php
namespace App\Controller\Feed\Baagl;

use App\Service\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

final class ConvertController extends AbstractController
{
    #[Route('/Feed/Baagl/convert', name: 'baagl_convert', methods: ['GET'])]
    public function __invoke(Db $db): BinaryFileResponse
    {
        // 1) Sloučení s mapou kategorií
        $rows = $db->select(
            'SELECT i.*, c.shoptet_id, c.shoptet_path
               FROM baagl_inbound i
               LEFT JOIN category c ON c.source = i.category_src
             ORDER BY i.id'
        );

        // 2) Přepočet cen – jednoduché pravidlo:
        // preferuj pc_czk_vat z ceníku; jinak pc_eur * kurz + zaokrouhlení na 9
        $EUR = 25.0;         // => můžeš si dát do configu/DB
        $DPH = 21;           // %
        $out = [];
        foreach ($rows as $r) {
            $priceCzk = (int)$r['pc_czk_vat'];
            if ($priceCzk <= 0 && $r['pc_eur'] > 0) {
                $gross = (float)$r['pc_eur'] * $EUR; // už je to koncová v € dle ceníku
                // zaokrouhli na ...9
                $priceCzk = (int)(round($gross / 10) * 10 - 1);
            }
            $out[] = [
                'SKU'          => $r['artikl'],
                'Název'        => $r['name'],
                'EAN'          => $r['ean'],
                'Varianta'     => $r['size_label'] ?: '',
                'Cena (Kč s DPH)' => $priceCzk,
                'DPH %'        => $DPH,
                'Kategorie ID' => $r['shoptet_id'] ?: '',
                'Kategorie'    => $r['shoptet_path'] ?: '',
            ];
        }

        // 3) Vygeneruj XLSX pro Shoptet (nebo pro tvůj „Froddo_vzor.xlsx“ – formát je podobný)
        $spread = new Spreadsheet();
        $sheet = $spread->getActiveSheet();
        $sheet->fromArray(array_keys($out[0] ?? ['SKU'=>null]), null, 'A1');
        $rowIdx = 2;
        foreach ($out as $row) {
            $sheet->fromArray(array_values($row), null, 'A'.$rowIdx++);
        }

        $file = sys_get_temp_dir().'/baagl_shoptet.xlsx';
        (new Xlsx($spread))->save($file);

        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'baagl_shoptet.xlsx');
        return $response;
    }
}
