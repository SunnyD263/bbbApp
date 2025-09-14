<?php
namespace App\Controller\Inventura;

use App\Service\Db; 
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ImportController extends AbstractController
{
    #[Route('/inventura/import', name: 'inventory_import', methods: ['GET','POST'])]
    public function __invoke(Request $req, Db $db): Response
    {
        // Pevně daná URL feedu
        $fileUrl = "https://www.babybebare.cz/export/products.xls?patternId=34&partnerId=3&hash=d3ee0093e7699b3c9f6dd88a6a19c048528f3900ad03510f3ff0ea3be9186846";

        if ($req->isMethod('POST')) {
            // CSRF
            if (!$this->isCsrfTokenValid('inventory_import', (string)$req->request->get('_token'))) {
                $this->addFlash('error', 'Neplatný CSRF token.');
                return $this->redirectToRoute('inventory_import');
            }

            // stáhnout do dočasného souboru
            $tmp = tempnam(sys_get_temp_dir(), 'inv_') . '.xls';
            $content = @file_get_contents($fileUrl);
            if ($content === false) {
                $this->addFlash('error', 'Nepodařilo se stáhnout soubor z pevné URL.');
                return $this->redirectToRoute('inventory_import');
            }
            file_put_contents($tmp, $content);

            // vybrat reader dle MIME
            $mime = @mime_content_type($tmp) ?: '';
            $reader = str_contains($mime, 'spreadsheetml') ? new Xlsx() : new Xls();
            $reader->setReadDataOnly(true);

            try {
                $sheet = $reader->load($tmp)->getActiveSheet();
                $highestRow = $sheet->getHighestRow();
                $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());

                // tabulka inventura od nuly
                $db->execute('DROP TABLE IF EXISTS inventura');
                $db->execute(
                    'CREATE TABLE inventura(
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    code VARCHAR(18) NULL,
                    name VARCHAR(255) NULL,
                    ean VARCHAR(18) NULL,
                    productNumber VARCHAR(50) NULL,
                    barva VARCHAR(50) NULL,
                    rozmer VARCHAR(50) NULL,
                    velikost VARCHAR(50) NULL,
                    velikostBoty INT NULL,
                    stock INT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
                );

                // čtení a batch insert
                $batch = [];
                $BATCH_SIZE = 400;

                for ($row = 2; $row <= $highestRow; $row++) {
                    $r = [];
                    for ($c = 1; $c <= $highestCol; $c++) {
                        $colLetter = Coordinate::stringFromColumnIndex($c);
                        $r[] = (string) $sheet->getCell($colLetter . $row)->getValue();
                    }
                    // mapování sloupců dle tvého XLS (indexováno od 0)
                    $batch[] = [
                        'code'          => $r[0]  ?? '',
                        'name'          => $r[2]  ?? '',
                        'ean'           => $r[3]  ?? '',
                        'productNumber' => $r[4]  ?? '',
                        'barva'         => $r[5]  ?? '',
                        'rozměr'        => $r[6]  ?? '',                        
                        'velikost'      => (string)($r[7] ?? ''),
                        'velikostBoty' => (int)($r[8] ?? 0),                        
                        'stock'         => (int)($r[10] ?? 0),
                    ];

                    if (count($batch) >= $BATCH_SIZE) {
                        $this->flushBatch($db, $batch);
                        $batch = [];
                    }
                }
                if ($batch) {
                    $this->flushBatch($db, $batch);
                }

                $count = (int)($db->select('SELECT COUNT(*) c FROM inventura')[0]['c'] ?? 0);
                $this->addFlash('success', sprintf('%d záznamů bylo nahráno.', $count));
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Chyba při načítání souboru: '.$e->getMessage());
            } finally {
                @unlink($tmp);
            }

            return $this->redirectToRoute('inventory_import');
        }

        // GET: jen zobraz tlačítko
        return $this->render('inventura/import.html.twig');
    }

    private function flushBatch(Db $db, array $rows): void
    {
        if (!$rows) return;

        // hromadný insert s placeholders
        $values = [];
        $params = [];
        foreach ($rows as $i => $r) {
            $values[] = '(?,?,?,?,?,?,?,?,?)';
            array_push(
                $params,
                $r['code'],
                $r['name'],
                $r['ean'],
                $r['productNumber'],
                $r['barva'],
                $r['rozměr'],
                $r['velikost'],
                $r['velikostBoty'],
                $r['stock']
            );
        }

        $sql = 'INSERT INTO inventura (code,name, ean, productNumber, barva,rozmer, velikost, velikostBoty, stock) VALUES '.implode(',', $values);
        $db->execute($sql, $params);
    }
}
