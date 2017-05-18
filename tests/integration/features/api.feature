Feature: auth

Scenario: Test return of create API
	Given the user "test" exists
	When "test" creates a new note with content "Foo"
	Then the response should have a status code "200"
	And the response should be a JSON array with the following mandatory values
		|key|value|
		|title|Foo|
		|content|Foo|

Scenario: Test return of get API with existing node
	Given the user "test" exists
	And "test" creates a new note with content "Foo"
	When "test" requests the notes list
	Then the response should have a status code "200"
	And the response should be a JSON array with a length of 1

Scenario: Test setting the Modified-Date
	Given the user "test" exists
	And "test" creates a new note with content "Foo"
	When "test" updates the modified-date of the note to 5
	Then the response should have a status code "200"
	And the response should be a JSON object with the following mandatory values
		|key|value|
		|title|Foo|
		|content|Foo|
		|modified|5|




