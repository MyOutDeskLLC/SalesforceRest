### Salesforce Rest API Example
Contains code for basic rest API implementation in php using username\password flow for API only account.

### Salesforce Setup
1. Salesforce Sandbox -> Setup -> Create -> Apps
2. Create new custom app with Oauth Enabled & Device Flow Enabled
3. Copy the Consumer Key & Consumer Secret
4. Login using API only user

#### Installation
```
composer myoutdeskllc/salesforcerest
```

#### Usage
Default API version is 42.0, production is off by default. Your wrapper can be tested using [a mock handler](http://docs.guzzlephp.org/en/stable/testing.html).

```php
use MyOutDesk\SalesforceRest\SalesforceClient;

$salesforceRest = new SalesforceRest(new \GuzzleHttp\Client());
$connected = $salesforceRest->connectApp(CONSUMER_KEY, CONSUMER_SECRET)
				->asUser(SALESFORCE_USER, SALESFORCE_PASSWORD)
				->authenticate();
if($connected) {
	// good to go
}
```

#### Create Record
```php
$salesforceRest->create('Lead', [
	'FirstName' => 'Unit',
	'LastName' => 'McTestFace',
	'Company' => 'MyOutDesk, LLC'
]);
```

#### Create Multiple Records
```php
// Requires api 42.0
$leadOne = [
    'firstName' => 'Test',
    'lastName' => 'McTestFace',
    'Company' => 'MyOutDesk, LLC'
];

$leadTwo = [
    'firstName' => 'TestTwo',
    'lastName' => 'McAlsoTestFace',
    'Company' => 'MyOutDesk, LLC'
];

$salesforceRest->insertCollection('Lead', [$leadOne, $leadTwo]);    
```

#### Get Record
```php
// Get all fields
$salesforceRest->get('Lead', ID);
// Get only specific fields
$salesforceRest->get('Lead', ID, ['Phone', 'customfield__c', 'email']);
```

#### Get Multiple Records
```php
// Requires api 42.0
$salesforceRest->getCollection('Lead', [ID, ANOTHER_ID], ['firstName', 'lastName']);
```

#### Update Record
```php
$salesforceRest->update('Account', ID, ['Phone' => '123-1234-123'])
```

#### Update Multiple Records
```php
// Requires api 42.0
$leadOne = [
    'id' => ID,
    'firstName' => 'LEAD ONE',
    // other fields here
];

$leadTwo = [
    'id' => ANOTHER_ID,
    'firstName' => 'LEAD TWO',
    // other fields here
];

$salesforceRest->updateCollection('Lead', [ $leadOne, $leadTwo ]);
```

#### Delete Record
```php
$salesforceRest->delete('Lead', ID);
```

#### Delete Multiple Records
```php
// Requires api 42.0
$salesforceRest->deleteCollection([ID, ANOTHER_ID]);
```

#### Search For Record
```php
$salesforceRest->search('FIND {unittest@mod.com} IN ALL FIELDS RETURNING Lead(Id, Name, Email)');
```