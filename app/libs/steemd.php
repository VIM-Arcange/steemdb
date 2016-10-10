<?php

use JsonRPC\Client;
use JsonRPC\HttpClient;

class steemd
{

  protected $host;
  protected $client;

  public function __construct($host)
  {
    $this->host = $host;
    $httpClient = new HttpClient($host);
    $httpClient->withoutSslVerification();
    $this->client = new Client($host, false, $httpClient);
  }

  public function getAccount($account)
  {
    try {
      return $this->client->call(0, 'get_accounts', [[$account]]);
    } catch (Exception $e) {
      return array();
    }
  }
  public function getAccountHistory($username, $limit = 100, $skip = -1)
  {
    try {
      return $this->client->call(0, 'get_account_history', [$username, $skip, $limit]);
    } catch (Exception $e) {
      return array();
    }
  }

  public function getProps($username, $limit = 100, $skip = -1)
  {
    try {
      return $this->client->call(0, 'get_dynamic_global_properties', []);
    } catch (Exception $e) {
      return array();
    }
  }

  public function getApi($name)
  {
    return $this->client->call(1, 'get_api_by_name', [$name]);
  }

  public function getFollowing($username, $limit = 100, $skip = -1)
  {
    $api = $this->getApi('follow_api');
    return $this->client->call($api, 'get_following', [$username, $skip, $limit]);
  }
}
