<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Aws\Sdk;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Sqs\SqsClient;
use Symfony\Component\HttpFoundation\Request;
use Ramsey\Uuid\Uuid;

use App\Form\ImageGeneratorType;

class MainController extends AbstractController
{
  /**
   * @Route("/", name="main")
   */
  public function new(Request $request)
  {
    $awsConfiguration = [
      'region'   => 'eu-west-1',
      'version'  => 'latest'
    ];

    $awsKey = getenv('AWS_KEY');
    $awsSecret = getenv('AWS_SECRET');
    if ($awsKey && $awsSecret) {
      $awsConfiguration['credentials'] = [
        'key' => $awsKey,
        'secret' => $awsSecret,
      ];
    }

    $form = $this->createForm(ImageGeneratorType::class);
    
    $data = [];
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $sqs = new SqsClient($awsConfiguration);
      
      $data = $form->getData();
      $numberOfImages = (int) $data['NumberOfImages'];
      
      $imageList = [];
      for ($imageNumber = 1; $imageNumber <= $numberOfImages; $imageNumber++) {
        $uuid = Uuid::uuid4(); 
        $sqs->sendMessage([
          'MessageBody' => $uuid->toString(),
          'QueueUrl' => getenv('SQS_URL')
        ]);
        $imageList[] = $uuid->toString();
      }
    }
      
    $ddb = new DynamoDbClient($awsConfiguration);
    $results = $ddb->scan([
      'TableName' => 'ImageList'
    ]);

    return $this->render('main/index.html.twig', [
      'form' => $form->createView(),
      'results' => $results
      'imageList' => $imageList
    ]);
  }
}
