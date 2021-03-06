<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Contracts\Cache\ItemInterface;

use Aws\Sdk;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Sqs\SqsClient;

use Ramsey\Uuid\Uuid;

class ApiController extends AbstractController
{
  /**
   * Builds a configuration array for AWS clients
   *
   * @param string $client The target client to build the config for or leave empty for a general one
   * @return array An associatiave array of configration dictated by AWS
   */
  private function awsConfiguration($client = 'general') : array
  {
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
  private function imageList() : array
  {
    $client = MemcachedAdapter::createConnection(getenv('MEMCACHED_ADDRESS'));
    $cache  = new MemcachedAdapter($client, $namespace = 'imageGenerator', $defaultLifetime = 300);

    return $cache->get('imageList', function (ItemInterface $item) {
      $item->expiresAfter((int) getenv('MEMCACHED_TTL_SECONDS'));

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
   * Sends messages to the defined queue in batches
   *
   * @param array $uuidList List of uuids
   * @param string $queueUrl The url of the queue to send the messages to
   * @return void
   */
  private function sendUuidsToSqs($uuidList, $queueUrl) : void
  {
    $sqs = new SqsClient($this->awsConfiguration('sqs'));

    while (count($uuidList)) {
      $entries = array_map(
        function ($uuid) {
          return [
            'Id' => $uuid,
            'MessageBody' => $uuid
          ];
        },
        array_splice($uuidList, 0, 10)
      );

      $sqs->sendMessageBatch([
        'Entries' => $entries,
        'QueueUrl' => $queueUrl
      ]);
    }
  }

  /**
   * Generates a list of id and puts them into the SQS queue for further processing
   *
   * @param integer $numberOfImages The number of images to be generated
   * @return array  The generated list of ids
   */
  private function generateImageList(int $numberOfImages) : array
  {
    $generatedUuidList = [];
    for ($imageNumber = 1; $imageNumber <= $numberOfImages; $imageNumber++) {
      $uuid = Uuid::uuid4(); 
      $generatedUuidList[] = $uuid->toString();
    }

    $this->sendUuidsToSqs($generatedUuidList, getenv('SQS_GENERATOR_URL'));

    return $generatedUuidList;
  }

  /**
   * Deletes all the images stored
   *
   * @return array The lsit of deleted ids
   */
  private function deleteAllImages() : array
  {
    $imageList = $this->imageList();
    $queuedImageIdList = array_map(
      function ($image) {
        return $image['filename'];
      },
      $imageList
    );
    $this->sendUuidsToSqs($queuedImageIdList, getenv('SQS_DELETER_URL'));

    return $queuedImageIdList;
  }

  /**
   * @Route("/api/images", name="getImages", methods={"GET"})
   */
  public function getImages() : JsonResponse
  {
    return new JsonResponse(
      $this->imageList()
    );
  }

  /**
   * @Route("/api/images", name="deleteImages", methods={"DELETE"})
   */
  public function deleteImages() : JsonResponse
  {
    return new JsonResponse([
      'deletedImageIdList' => $this->deleteAllImages()
    ]);
  }

  /**
   * @Route("/api/images", name="postImages", methods={"POST"})
   */
  public function postImages(Request $request) : JsonResponse
  {
    $numberOfImages = (int) $request->request->get('numberOfImages');
    $generatedUuids = $this->generateImageList($numberOfImages);

    return new JsonResponse([
      'generatedImageIdList' => $generatedUuids
    ]);
  }
}
