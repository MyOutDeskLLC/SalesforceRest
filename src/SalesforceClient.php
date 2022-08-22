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
    private $version;

    public function __construct(Client $client, $production = false, $version = 'v42.0') {
        $this->client = $client;
        $this->authenticator = new SalesforceAuthenticator($this->client, $production);
        $this->version = $version;
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
        $this->instanceUrl .= '/services/data/' .$this->version. '/';
        if(!isset($this->instanceUrl, $this->accessToken)) {
            return false;
        }
        return true;
    }

    /**
     * Sets the instance url
     *
     * @param $instanceUrl
     * @return void
     */
    public function setInstanceUrl($instanceUrl)
    {
        $this->instanceUrl = $instanceUrl;
    }

    /**
     * Sets the access token to enable outside caching of credentials for certain time periods
     *
     * @param $accessToken
     * @return void
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Returns the access token, if set
     *
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Returns the instance url, if set
     *
     * @return void
     */
    public function getInstanceUrl()
    {
        return $this->instanceUrl;
    }

    /**
     * Restores a state, potentially from cached credentials
     *
     * @param $accessToken
     * @param $instanceUrl
     * @return void
     */
    public function restore($accessToken, $instanceUrl)
    {
        $this->accessToken = $accessToken;
        $this->instanceUrl = $instanceUrl;
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
        $response = $this->client->request('GET', $this->instanceUrl . 'search/', [
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
     * Searches salesforce using a given SOQL string
     * 
     * SELECT Id FROM Opportunity WHERE AccountId = \'AccountIdHere\'
     *
     * @param $query
     * @return mixed
     */
    public function query($query)
    {
        $response = $this->client->request('GET', $this->instanceUrl . 'query/', [
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
     * Gets the records for a given object type (requires Ids, fields)
     *
     * @param $object
     * @param array $ids
     * @param array $fields
     * @return mixed
     */
    public function getCollection($object, array $ids, array $fields)
    {
        $allIds = implode($ids, ',');
        $allFields = implode($fields, ',');

        $response = $this->client->request('GET', $this->instanceUrl . "composite/sobjects/$object", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json'
            ],
            'query' => [
                'ids' => $allIds,
                'fields' => $allFields
            ]
        ]);
        return json_decode((string)$response->getBody(), true);
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
     * Updates multiple records at once, using the given object type
     * Make sure your objects have ID's in the properties
     *
     * @param $object
     * @param array $collection
     * @return bool
     */
    public function updateCollection($object, array $collection)
    {
        $processedCollection = [];
        foreach($collection as $item) {
            $processedCollection[] = array_merge(['attributes' => ['type' => $object]], $item);
        }
        $response = $this->client->request('PATCH', $this->instanceUrl . 'composite/sobjects', [
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
     * Deletes records with the given Ids
     *
     * @param $ids
     * @return bool
     */
    public function deleteCollection($ids)
    {
        $allIds = implode($ids, ',');
        $response = $this->client->request('DELETE', $this->instanceUrl . 'composite/sobjects', [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json'
            ],
            'query' => [
                'ids' => $allIds
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
        $response = $this->client->request('GET', $this->instanceUrl . 'jobs/ingest', [
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

    /**
     * Returns metadata about a given report
     * 
     * @see https://developer.salesforce.com/docs/atlas.en-us.api_analytics.meta/api_analytics/sforce_analytics_rest_api_get_reportmetadata.htm
     * 
     * @param $id salesforce ID of the report
     * @return mixed
     */
    public function getReportMetadata($id)
    {
        $response = $this->client->request('GET', $this->instanceUrl . "analytics/reports/$id/describe", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json'
            ]
        ]);
        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Deletes the report by the given ID (must not be in use by a dashboard)
     * 
     * @see https://developer.salesforce.com/docs/atlas.en-us.api_analytics.meta/api_analytics/sforce_analytics_rest_api_delete_report.htm
     * 
     * @param $id salesforce id of the report 
     * @return boolean
     */
    public function deleteReport($id) 
    {
        $response = $this->client->request('DELETE', $this->instanceUrl . "analytics/reports/$id", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json'
            ]
        ]);
        return ($response->getStatusCode() === 204);
    }

    /**
     * Gets metadata for the given dashboard
     * 
     * @see https://developer.salesforce.com/docs/atlas.en-us.api_analytics.meta/api_analytics/analytics_api_dashboard_example_get_dashboard_metadata.htm
     * 
     * @param $id salesforce id of the dashboard
     * @return mixed
     */
    public function getDashboardMetadata($id)
    {
        $response = $this->client->request('GET', $this->instanceUrl . "analytics/dashboards/$id/describe", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json'
            ]
        ]);
        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Gets the given dashboard results
     * 
     * @see https://developer.salesforce.com/docs/atlas.en-us.api_analytics.meta/api_analytics/analytics_api_dashboard_get_results.htm
     * 
     * @param $id salesforce id of the dashboard
     * @return mixed
     */
    public function getDashboardResults($id)
    {
        $response = $this->client->request('GET', $this->instanceUrl . "analytics/dashboards/$id", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json'
            ]
        ]);
        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Deletes the given dashboard
     * 
     * @see https://developer.salesforce.com/docs/atlas.en-us.api_analytics.meta/api_analytics/analytics_api_dashboard_delete.htm
     * 
     * @param $id salesforce id of the dashboard
     * @return boolean
     */
    public function deleteDashboard($id)
    {
        $response = $this->client->request('DELETE', $this->instanceUrl . "analytics/dashboards/$id", [
            'headers' => [
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json'
            ]
        ]);
        return ($response->getStatusCode() === 204);
    }
}