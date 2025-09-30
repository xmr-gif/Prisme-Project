<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Vérifier si l'utilisateur est déjà connecté
        $user = $this->getUser();

        return $this->render('home/index.html.twig', [
            'controller_name' => 'PRISME - Accueil',
            'show_auth_popup' => !$user, // Montrer popup seulement si pas connecté
            'user' => $user
        ]);
    }

    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'email', 'profile'
            ], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(): Response
    {
        // Cette route sera gérée par l'authenticator
        return $this->redirectToRoute('app_dashboard');
    }
}
