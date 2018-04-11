<?php

namespace MyOutDesk\SalesforceRest;

use MyOutDesk\SalesforceRest\SalesforceAuthenticator;

class SalesforceClient {
	private $authenticator;
	private $instanceUrl;
	
	private $headers;
	private $accessToken;

	public function __construct(\GuzzleHttp\Client $client) {
		$this->client = $client;
		$this->authenticator = new SalesforceAuthenticator($this->client);
	}

	public function connectApp($clientId, $clientSecret) 
	{	
		$this->authenticator->configureApp($clientId, $clientSecret);
		return $this;
	}

	public function asUser($username, $password)
	{
		$this->authenticator->configureUser($username, $password);
		return $this;
	}

	public function authenticate()
	{
		$this->authenticator->authenticate();
		$this->instanceUrl = $this->authenticator->getInstanceUrl();
		$this->accessToken = $this->authenticator->getToken();
		$this->instanceUrl .= "/services/data/v20.0/";
		if(!isset($this->instanceUrl, $this->accessToken)) {
			return false;
		}
		return true;
	}

	public function search($query)
	{
		$response = $this->client->request('GET', $this->instanceUrl . "search/", [
			'headers' => [
			    'Authorization' => "Bearer $this->accessToken",
			    'Content-Type' => 'application/json'
			], 
			'query' => [
				'q' => $query
			]
		]);
		return json_decode((string)$response->getBody(), true);
	}

	public function get($object, $id, $fields = [])
	{
		$allFields = implode($fields, ",");
		$response = $this->client->request('GET', $this->instanceUrl . "sobjects/$object/$id" . ((!empty($fields)) ? "?fields=$allFields" : ""), [
			'headers' => [
			    'Authorization' => "Bearer $this->accessToken",
			    'Content-Type' => 'application/json'
			]
		]);
		return json_decode((string)$response->getBody(), true);
	}

	public function create($object, $properties)
	{
		$response = $this->client->request('POST', $this->instanceUrl . "sobjects/$object", [
			'headers' => [
			    'Authorization' => "Bearer $this->accessToken",
			    'Content-Type' => 'application/json'
			],
			'json' => $properties
		]);
		return json_decode((string)$response->getBody(), true);
	}

	public function update($object, $id, $properties)
	{
		$response = $this->client->request('PATCH', $this->instanceUrl . "sobjects/$object/$id", [
			'headers' => [
			    'Authorization' => "Bearer $this->accessToken",
			    'Content-Type' => 'application/json'
			],
			'json' => $properties
		]);
		return ($response->getStatusCode() === 204);
	}

	public function delete($object, $id)
	{
		$response = $this->client->request('DELETE', $this->instanceUrl . "sobjects/$object/$id", [
			'headers' => [
			    'Authorization' => "Bearer $this->accessToken",
			    'Content-Type' => 'application/json'
			]
		]);
		return ($response->getStatusCode() === 204);
	}
}