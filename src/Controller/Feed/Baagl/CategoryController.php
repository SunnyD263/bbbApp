<?php
namespace App\Controller\Feed\Baagl;

use App\Service\Db;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CategoryController extends AbstractController
{
    #[Route('/Feed/Baagl/category', name: 'category_index', methods: ['GET','POST'])]
    public function __invoke(Request $r, Db $db): Response
    {
        if ($r->isMethod('POST')) {
            $db->insertRow('category', [
                'source' => trim((string)$r->request->get('source')),
                'shoptet_id' => (int)$r->request->get('shoptet_id'),
                'shoptet_path' => trim((string)$r->request->get('shoptet_path')),
            ]);
            return $this->redirectToRoute('category_index');
        }

        $categories = $db->select('SELECT * FROM category ORDER BY source');
        return $this->render('Feed/Baagl/category.html.twig', ['rows'=>$categories]);
    }
}
