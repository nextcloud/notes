<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;

class FeatureContext implements Context {
	/** @var \GuzzleHttp\Message\ResponseInterface */
	private $response;
	/** @var string */
	private $mappedUserId;

	/**
	 * @Given the user :user exists
	 * @param string $user
	 */
	public function theUserExists($user) {
		$this->mappedUserId = $user . '' . md5(random_bytes(32));
		shell_exec('OC_PASS=test ' . __DIR__ . '/../../../../../../occ user:add ' . $this->mappedUserId . ' --password-from-env');
	}


	/**
	 * @When :user requests the notes list
	 */
	public function requestsTheNotesList($user) {
		$client = new GuzzleHttp\Client();

		$this->response = $client->get(
			'http://localhost:8080/index.php/apps/notes/api/v0.2/notes',
			[
				'auth' => [
					$this->mappedUserId,
					'test',
				],
			]
		);
	}


	/**
	 * @When :user creates a new note with content :content
	 */
	public function createsANewNoteWithContent($user, $content) {
		$client = new GuzzleHttp\Client();
		$client->get(
			'http://localhost:8080/stable9/index.php/apps/notes',
			[
				'auth' => [
					$this->mappedUserId,
					'test',
				],
			]
		);
		$this->response = $client->post(
			'http://localhost:8080/index.php/apps/notes/api/v0.2/notes',
			[
				'form_params' => [
					'content' => $content,
				],
				'auth' => [
					$this->mappedUserId,
					'test',
				],
			]
		);
	}

	/**
	 * @Then the response should have a status code :code
	 * @param string $code
	 * @throws InvalidArgumentException
	 */
	public function theResponseShouldHaveAStatusCode($code) {
		$currentCode = $this->response->getStatusCode();
		if ($currentCode !== (int)$code) {
			throw new InvalidArgumentException(
				sprintf(
					'Expected %s as code got %s',
					$code,
					$currentCode
				)
			);
		}
	}

	/**
	 * @Then the response should be a JSON array with the following mandatory values
	 * @param TableNode $table
	 * @throws InvalidArgumentException
	 */
	public function theResponseShouldBeAJsonArrayWithTheFollowingMandatoryValues(TableNode $table) {
		$expectedValues = $table->getColumnsHash();
		$realResponseArray = json_decode($this->response->getBody()->getContents(), true);

		foreach ($expectedValues as $value) {
			if ((string)$realResponseArray[$value['key']] !== (string)$value['value']) {
				throw new InvalidArgumentException(
					sprintf(
						'Expected %s for key %s got %s',
						(string)$value['value'],
						$value['key'],
						(string)$realResponseArray[$value['key']]
					)
				);
			}
		}
	}

	/**
	 * @Then the response should be a JSON array with a length of :length
	 * @param int $length
	 * @throws InvalidArgumentException
	 */
	public function theResponseShouldBeAJsonArrayWithALengthOf($length) {
		$realResponseArray = json_decode($this->response->getBody()->getContents(), true);
		if((int)count($realResponseArray) !== (int)$length) {
			throw new InvalidArgumentException(
				sprintf(
					'Expected %d as length got %d',
					$length,
					count($realResponseArray)
				)
			);
		}
	}
}
