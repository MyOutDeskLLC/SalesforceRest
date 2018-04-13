<?php

namespace MyOutDesk\SalesforceRest;

use GuzzleHttp\Client;

/**
 * Class SalesforceClient
 *
 * Simple wrapper class to handle Salesforce CRUD operations
 *
 * @package MyOutDesk\SalesforceRest
 */
class SalesforceClient {
	private $authenticator;
	private $instanceUrl;
	private $accessToken;

	const API_VERSION = "v42.0";

	public function __construct(Client $client, $production = false) {
		$this->client = $client;
		$this->authenticator = new SalesforceAuthenticator($this->client, $production);
	}

    /**
     * Which app to authenticate as in salesforce
     *
     * @param $consumerKey Given by Salesforce when adding an application
     * @param $consumerSecret Given by Salesforce when adding an application
     * @return $this
     */
	public function connectApp($consumerKey, $consumerSecret)
	{
		$this->authenticator->configureApp($consumerKey, $consumerSecret);
		return $this;
	}

    /**
     * Authenticates as a given user. Use API user on production and your own user on sandbox
     *
     * @param $username
     * @param $password
     * @return $this
     */
	public function asUser($username, $password)
	{
		$this->authenticator->configureUser($username, $password);
		return $this;
	}

    /**
     * Attempts to authenticate with the given app and user configuration
     *
     * @return bool
     */
	public function authenticate()
	{
		$this->authenticator->authenticate();
		$this->instanceUrl = $this->authenticator->getInstanceUrl();
		$this->accessToken = $this->authenticator->getToken();
		$this->instanceUrl .= '/services/data/' .self::API_VERSION. '/';
		if(!isset($this->instanceUrl, $this->accessToken)) {
			return false;
		}
		return true;
	}

    /**
     * Searches salesforce using the given string format:
     *
     * FIND {test@mod.com} IN ALL FIELDS RETURNING Lead(Id, Name, Email)
     *
     * @param $query
     * @return mixed
     */
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

    /**
     * Returns a record from salesforce if it exists
     *
     * @param $object the type of object (Lead, Account, Opportunity, etc)
     * @param $id the salesforce ID
     * @param array $fields optional, if you want only specific fields
     * @return mixed
     */
	public function get($object, $id, array $fields = [])
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

    /**
     * Creates a record type with the properties given
     *
     * @param $object Type of object
     * @param $properties key value pairs for the object
     * @return mixed
     */
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

    /**
     * Updates a given record with the new properties specified
     *
     * @param $object
     * @param $id
     * @param $properties
     * @return bool true if successful, false if not
     */
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

    /**
     * Deletes the given object type with the given ID
     *
     * @param $object
     * @param $id
     * @return bool true if successful, false if not
     */
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

    /**
     * Inserts multiple records at once, using the given object type
     *
     * @param $object object to insert (Lead, Account, Etc)
     * @param array $collection records to insert
     * @return bool
     */
	public function insertCollection($object, array $collection)
    {
        $processedCollection = [];
        foreach($collection as $item) {
            $processedCollection[] = array_merge(['attributes' => ['type' => $object]], $item);
        }
        $response = $this->client->request('POST', $this->instanceUrl . 'composite/sobjects', [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'allOrNone' => true,
                'records' => $processedCollection
            ]
        ]);
        return ($response->getStatusCode() === 200);
    }

    /**
     * Creates a bulk API job
     *
     * @param $object
     * @param $contentType
     * @param $operation
     * @return mixed
     */
	public function createJob($object, $contentType, $operation)
    {
        $response = $this->client->request('POST', $this->instanceUrl . "jobs/ingest", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'object' => $object,
                'contentType' => $contentType,
                'operation' => $operation
            ]
        ]);
        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Gets the status of an existing bulk API job
     *
     * @param $jobId
     * @return mixed
     */
    public function getJobStatus($jobId)
    {
        $response = $this->client->request('GET', $this->instanceUrl . "jobs/ingest/$jobId", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json'
            ],
        ]);
        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Gets all job statuses
     *
     * @return mixed
     */
    public function getAllJobStatus()
    {
        $response = $this->client->request('GET', $this->instanceUrl . "jobs/ingest", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json'
            ],
        ]);
        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Aborts the given job
     *
     * @param $jobId
     * @return mixed
     */
    public function abortJob($jobId)
    {
        $response = $this->client->request('PATCH', $this->instanceUrl . "jobs/ingest/$jobId", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'state' => 'Aborted'
            ]
        ]);
        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Closes the given job, allowing it to process
     *
     * @param $jobId
     * @return mixed
     */
    public function closeJob($jobId)
    {
        $response = $this->client->request('PATCH', $this->instanceUrl . "jobs/ingest/$jobId", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'state' => 'UploadComplete'
            ]
        ]);
        return json_decode((string)$response->getBody(), true);
    }
}