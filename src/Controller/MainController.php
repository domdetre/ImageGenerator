<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Form\ImageGeneratorType;

class MainController extends AbstractController
{
  /**
   * @Route("/", name="main")
   * 
   * The main function to display the start page
   */
  public function main(Request $request) : Response
  {
    $form = $this->createForm(ImageGeneratorType::class);

    return $this->render('main/index.html.twig', [
      'form' => $form->createView()
    ]);
  }
}
