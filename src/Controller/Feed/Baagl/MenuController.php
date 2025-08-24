<?php
namespace App\Controller\Feed\Baagl;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class MenuController extends AbstractController
{
    #[Route('/shoptet/menu', name: 'shoptet_post', methods: ['POST'])]
    public function __invoke(Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        // CSRF
        $token = (string)$request->request->get('_token');
        if (!$csrf->isTokenValid(new CsrfToken('post_baagl', $token))) {
            throw $this->createAccessDeniedException('Neplatný CSRF token.');
        }

        $source = (string)$request->request->get('source', '');
        return match ($source) {
            'baagl'  => $this->redirectToRoute('baagl_import'),
            'update' => $this->redirectToRoute('baagl_convert'),
            default  => $this->redirectToRoute('home'),
        };
    }
}
