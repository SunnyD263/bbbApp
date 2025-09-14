<?php
namespace App\Controller\Feed\Baagl;

use App\Service\Db;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

final class VideoExportController extends AbstractController
{
    #[Route('/Feed/Baagl/video.csv', name: 'video_export', methods: ['GET'])]
    public function __invoke(Db $db): StreamedResponse
    {
        // Zde si vyber, odkud bereš párování SKU->video (může být nová tabulka)
        $rows = $db->select('SELECT artikl AS sku, "" AS video FROM baagl WHERE 1=0'); // TODO

        return new StreamedResponse(function() use($rows) {
            $out = fopen('php://output', 'w');
            fputs($out, "SKU;VideoURL\n");
            foreach ($rows as $r) {
                fputs($out, ($r['sku'] ?? '').';'.($r['video'] ?? '')."\n");
            }
            fclose($out);
        }, 200, ['Content-Type'=>'text/csv; charset=utf-8']);
    }
}
