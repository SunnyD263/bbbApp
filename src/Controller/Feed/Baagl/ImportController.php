<?php
namespace App\Controller\Feed\Baagl;

use App\Service\Db;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ImportController extends AbstractController
{
    #[Route('/Feed/Baagl/import', name: 'baagl_import', methods: ['GET','POST'])]
    public function __invoke(Request $r, Db $db): Response
    {
    // pevně daný URL souboru
    $fileUrl = "https://www.babybebare.cz/export/products.xls?patternId=34&partnerId=3&hash=d3ee0093e7699b3c9f6dd88a6a19c048528f3900ad03510f3ff0ea3be9186846";

    if ($req->isMethod('POST')) {
        $tmp = tempnam(sys_get_temp_dir(), 'inv_') . '.xls';
        $content = @file_get_contents($fileUrl);
        if ($content === false) {
            $this->addFlash('error', 'Nepodařilo se stáhnout soubor z URL.');
            return $this->redirectToRoute('inventory_import');
        }
        file_put_contents($tmp, $content);

        $mime = @mime_content_type($tmp) ?: '';
        $reader = str_contains($mime,'spreadsheetml') ? new Xlsx() : new Xls();
        $reader->setReadDataOnly(true);

        try {
            $sheet = $reader->load($tmp)->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());

            // drop & create table inventura
            $db->execute('DROP TABLE IF EXISTS inventura');
            $db->execute(
                'CREATE TABLE inventura(
                   id INT AUTO_INCREMENT PRIMARY KEY,
                   name VARCHAR(255),
                   ean VARCHAR(18),
                   productNumber VARCHAR(50),
                   barva VARCHAR(50),
                   velikost INT,
                   stock INT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );

            $batch = [];
            $BATCH_SIZE = 400;

            for ($row = 2; $row <= $highestRow; $row++) {
                $r = [];
                for ($c = 1; $c <= $highestCol; $c++) {
                    $r[] = (string)$sheet->getCellByColumnAndRow($c, $row)->getValue();
                }
                // mapování dle původního souboru
                $batch[] = [
                    'name'          => $r[2]  ?? '',
                    'ean'           => $r[3]  ?? '',
                    'productNumber' => $r[4]  ?? '',
                    'barva'         => $r[5]  ?? '',
                    'velikost'      => (int)($r[6] ?? 0),
                    'stock'         => (int)($r[7] ?? 0),
                ];

                if (count($batch) >= $BATCH_SIZE) {
                    $this->flushBatch($db, $batch);
                    $batch = [];
                }
            }
            if ($batch) { $this->flushBatch($db, $batch); }

            $count = $db->select('SELECT COUNT(*) c FROM inventura')[0]['c'] ?? 0;
            $this->addFlash('success', sprintf('%d záznamů bylo nahráno.', $count));
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Chyba při načítání souboru: '.$e->getMessage());
        } finally {
            @unlink($tmp);
        }

            $this->addFlash('success', "Načteno $count záznamů do baagl_inbound.");
            return $this->redirectToRoute('baagl_import');
        }

        $rows = $db->select('SELECT * FROM baagl_inbound ORDER BY id DESC LIMIT 50');
        return $this->render('Feed/Baagl/baagl_import.html.twig', ['rows'=>$rows]);
    }
}
