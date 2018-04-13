<?php

namespace MyOutDesk\SalesforceRest;

use GuzzleHttp\Exception\ClientException;

/**
 * Class SalesforceAuthenticator
 *
 * Wrapper class to hold configuration data and attempt authentication to Salesforce
 *
 * @package MyOutDesk\SalesforceRest
 */
class SalesforceAuthenticator {
    private $client;

    private $accessToken;
    private $instanceUrl;
    private $baseUrl = 'https://test.salesforce.com/services/oauth2/token';

    private $consumerKey;
    private $consumerSecret;
    private $username;
    private $password;

    public function __construct($client, $production = false)
    {
        $this->client = $client;
        if($production) {
            $this->baseUrl = 'https://login.salesforce.com/services/oauth2/token';
        }
    }

    /**
     * Returns the instance URL to use for API calls
     *
     * @return mixed
     */
    public function getInstanceUrl()
    {
        return $this->instanceUrl;
    }

    /**
     * Returns the token to attach to all API calls
     *
     * @return mixed
     */
    public function getToken()
    {
        return $this->accessToken;
    }

    /**
     * Sets the key and secret of this application
     *
     * @param $consumerKey
     * @param $consumerSecret
     */
    public function configureApp($consumerKey, $consumerSecret)
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
    }

    /**
     * Sets the user, specific to this authentication flow
     *
     * @param $username
     * @param $password
     */
    public function configureUser($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Authenticates using the username \ password web server flow
     *
     * @return bool
     */
    public function authenticate()
    {
        try {
            $response = $this->client->request('POST', $this->baseUrl, [
                'form_params' => [
                    'grant_type'    => "password",
                    'client_id'     => $this->consumerKey,
                    'client_secret' => $this->consumerSecret,
                    'username'      => $this->username,
                    'password'      => $this->password,
                ],
            ]);
        } catch (ClientException $exception) {
            throw $exception;
        }
        $response = json_decode((string)$response->getBody(), true);
        $this->accessToken = $response['access_token'];
        $this->instanceUrl = $response['instance_url'];
        unset($this->username, $this->password, $this->consumerKey, $this->consumerSecret);
        return true;
    }
}