<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiControllerTest extends WebTestCase
{
  public function testGetImages()
  {
    $client = static::createClient();

    $client->request('GET', '/api/images');

    $this->assertEquals(
      200, 
      $client->getResponse()->getStatusCode()
    );
    $this->assertEquals(
      [], 
      $client->getResponse()->getContent()
    );
  }

  public function testDeleteImages()
  {
    $client = static::createClient();
    
    $client->request('DELETE', '/api/images');

    $this->assertEquals(
      200, 
      $client->getResponse()->getStatusCode()
    );
    $this->assertEquals(
      [], 
      $client->getResponse()->getContent()
    );
  }

  public function testPostImages()
  {
    $client = static::createClient();
    
    $client->request('POST', '/api/images', [], [], [], 1);

    $this->assertEquals(
      200, 
      $client->getResponse()->getStatusCode()
    );
    $this->assertEquals(
      1, 
      count($client->getResponse()->getContent())
    );
  }
}