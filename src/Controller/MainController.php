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
   * Builds a configuration array for AWS clients
   *
   * @param string $client The target client to build the config for or leave empty for a general one
   * @return array An associatiave array of configration dictatedd by AWS
   */
  private function awsConfiguration($client = 'general') {
    $awsConfiguration = [
      'region'   => getenv('AWS_DEFAULT_REGION') ?? 'eu-west-1',
      'version'  => 'latest'
    ];

    if (getenv('APP_ENV') === 'local') {
      $awsConfiguration['credentials'] = [
        'key' => getenv('AWS_ACCESS_KEY_ID'),
        'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
      ];
    }

    switch($client) {
      case 'dynamodb':
        $awsConfiguration['endpoint'] = 'http://dynamodb:8000';
    }

    return $awsConfiguration;
  }

  /**
   * @Route("/", name="main")
   * 
   * The main function that fetches the list of images form the db, processes the from and display the results
   */
  public function main(Request $request)
  {
    $form = $this->createForm(ImageGeneratorType::class);

    $results = [];
    $generatedUuids = [];
    $data = [];
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $sqs = new SqsClient($this->awsConfiguration());
      
      $data = $form->getData();
      $numberOfImages = (int) $data['NumberOfImages'];
      
      for ($imageNumber = 1; $imageNumber <= $numberOfImages; $imageNumber++) {
        $uuid = Uuid::uuid4(); 
        $sqs->sendMessage([
          'MessageBody' => $uuid->toString(),
          'QueueUrl' => getenv('SQS_URL')
        ]);
        $generatedUuids[] = $uuid->toString();
      }
    }
      
    $ddb = new DynamoDbClient($this->awsConfiguration('dynamodb'));
    $results = $ddb->scan([
      'TableName' => 'ImageList'
    ]);

    $imageList = array_map(
      function ($item) {
        return [
          'filename' => $item['filename']['S'],
          'extension' => $item['extension']['S']
        ];
      },
      $results['Items']
    );

    return $this->render('main/index.html.twig', [
      'form' => $form->createView(),
      'imageList' => $imageList,
      'generatedUuids' => $generatedUuids,
    ]);
  }
}
