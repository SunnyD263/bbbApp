<?php

namespace App\Controller\Rezervace;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class SetupController extends AbstractController
{
    #[Route('/rezervace/nastaveni', name: 'reservations_setup', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('rezervace/setup.html.twig');
    }
}
