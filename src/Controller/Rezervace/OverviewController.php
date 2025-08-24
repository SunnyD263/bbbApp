<?php

namespace App\Controller\Rezervace;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class OverviewController extends AbstractController
{
    #[Route('/rezervace/prehled', name: 'reservations_overview', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('reservace/overview.html.twig');
    }
}
