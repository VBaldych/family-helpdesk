<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

class RedirectController extends AbstractController
{
    public function __construct(private Security $security)
    {
    }

    #[Route(path: '/', name: 'entry_page')]
    public function index(): Response
    {
        if ($this->security->getUser()) {
            return $this->redirectToRoute('user_homepage');
        }

        return $this->redirectToRoute('app_login');
    }
}