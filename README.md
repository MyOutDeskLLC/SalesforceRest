### Salesforce Rest API Example
Contains code for basic rest API implementation in php

### Salesforce Setup
1. Salesforce Sandbox -> Setup -> Create -> Apps
2. Create new custom app with Oauth Enabled & Device Flow Enabled
3. Copy the Consumer Key & Consumer Secret
4. Login using your user or the API user

### Installation
```
composer myoutdeskllc/salesforcerest
```

### Usage
```php
use MyOutDesk\SalesforceRest\SalesforceClient;

$salesforceRest = new SalesforceRest();
$connected = $salesforceRest->connectApp(CONSUMER_KEY, CONSUMER_SECRET)
				->asUser(SALESFORCE_USER, SALESFORCE_PASSWORD)
				->authenticate();
if($connected) {
	// good to go
}
```

### Create Record
```php
$salesforceRest->create('Lead', [
	'FirstName' => 'Unit',
	'LastName' => 'McTestFace',
	'Company' => 'MyOutDesk, LLC'
]);
```

### Create Multiple Records
API version 42.0 required. Supports up to 200 records at a time.
```php
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

### Read Record
```php
// Get all fields
$salesforceRest->get('Lead', '00Q2F000002yJUk');
// Get only specific fields
$salesforceRest->get('Lead', '00Q2F000002yJUk', ['Phone', 'customfield__c', 'email']);
```

### Update Record
```php
$salesforceRest->update('Account', '0012F00000DQe3A', ['Phone' => '123-1234-123'])
```

### Delete Record
```php
$salesforceRest->delete('ManualTask__c', 'a1B2F000000BuCn');
```

### Search For Record
```php
$salesforceRest->search('FIND {unittest@mod.com} IN ALL FIELDS RETURNING Lead(Id, Name, Email)');
```