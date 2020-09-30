<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Aws\Sdk;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Sqs\SqsClient;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Contracts\Cache\ItemInterface;

use App\Form\ImageGeneratorType;

class MainController extends AbstractController
{
  /**
   * Builds a configuration array for AWS clients
   *
   * @param string $client The target client to build the config for or leave empty for a general one
   * @return array An associatiave array of configration dictated by AWS
   */
  private function awsConfiguration($client = 'general') {
    $awsConfiguration = [
      'region'   => getenv('AWS_DEFAULT_REGION') ?: 'eu-west-1',
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
        $endpoint = getenv('DYNAMODB_ENDPOINT');
        $endpoint ? $awsConfiguration['endpoint'] = $endpoint : null;
    }

    return $awsConfiguration;
  }

  /**
   * Fetches the list of images.
   * Uses a dynamodb scan to retrieve the list and caches in memcached.
   *
   * @return array The indexed list of images
   */
  private function imageList() {
    $client = MemcachedAdapter::createConnection(getenv('MEMCACHED_ADDRESS'));
    $cache  = new MemcachedAdapter($client, $namespace = 'imageGenerator', $defaultLifetime = 300);

    return $cache->get('imageList', function (ItemInterface $item) {
      $item->expiresAfter(30);

      $ddb = new DynamoDbClient($this->awsConfiguration('dynamodb'));
      $results = $ddb->scan([
        'TableName' => getenv('DYNAMODB_TABLE')
      ]);

      return array_map(
        function ($item) {
          return [
            'filename' => $item['filename']['S'],
            'extension' => $item['extension']['S'] ?? 'jpg',
            'ctime' => time()
          ];
        },
        $results['Items']
      );
    });
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

    return $this->render('main/index.html.twig', [
      'form' => $form->createView(),
      'generatedUuids' => $generatedUuids,
    ]);
  }

  /**
   * @Route("/images", name="images")
   * An endpoint to return the list of images in JSON format
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function images()
  {
    $response = new Response();

    $response->headers->set('Content-Type', 'application/json');
    $response->headers->set('Access-Control-Allow-Origin', '*');

    $response->setContent(json_encode($this->imageList()));
    
    return $response;
  }
}
