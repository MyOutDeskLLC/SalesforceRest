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
use SalesforceRest\SalesforceRest;

$salesforceRest = new SalesforceRest();
$connected = $salesforceRest->connectApp(CONSUMER_KEY, CONSUMER_SECRET)
				->asUser(SALESFORCE_USER, SALESFORCE_PASSWORD)
				->authenticate();
if($connected) {
	// good to go
}
```

### Record Creation
```php
	$salesforceRest->create('Lead', [
		'FirstName' => 'Unit',
		'LastName' => 'McTestFace',
		'Company' => 'MyOutDesk, LLC'
	]);
```