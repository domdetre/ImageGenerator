<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class GeneratorController extends AbstractController
{
    /**
     * @Route("/generator", name="generator")
     */
    public function index()
    {
        return $this->render('generator/index.html.twig', [
            'controller_name' => 'GeneratorController',
        ]);
    }
}
