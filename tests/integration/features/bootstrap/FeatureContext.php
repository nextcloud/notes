<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Assert\Assertion;
//use OCA\Notes\Controller\OCP\AppFramework\App;

require_once __DIR__ . '/../../vendor/autoload.php';

class FeatureContext implements Context {
	/** @var GuzzleHttp\Message\ResponseInterface */
	private $response;
	/** @var string */
	private $mappedUserId;
	/** @var  string */
	private $baseUrl;
	/** @var  OCP\Files\IRootFolder */
	private $fs;
	/** @var string */
	private $notesFolder = '/test/files/Notes';

	public function __construct($baseUrl, $admin) {
		$this->baseUrl = $baseUrl;
	}

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
			$this->baseUrl . 'index.php/apps/notes/api/v0.2/notes',
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
			$this->baseUrl . 'index.php/apps/notes',
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
		Assertion::same(count($realResponseArray), (int)$length);
	}

	/**
	 * @When :user updates the modified-date of the note to :date
	 */
	public function updatesTheModifiedDateOfTheNoteTo($user, $date)
	{
		$client = new GuzzleHttp\Client();

		$latestNote = json_decode($this->response->getBody());
		$latestNote->modified = $date;

		$this->response = $client->put(
			$this->baseUrl . 'index.php/apps/notes/api/v0.2/notes/' . $latestNote->id,
			[
				'auth' => [
					$this->mappedUserId,
					'test',
				],
				'json' => $latestNote
			]

		);

		$newNote = json_decode($this->response->getBody());

		Assertion::eq($newNote->modified, 5);
	}

	/**
	 * @Then the response should be a JSON object with the following mandatory values
	 */
	public function theResponseShouldBeAJsonObjectWithTheFollowingMandatoryValues(TableNode $table)
	{
		$expectedValues = $table->getColumnsHash();
		$realResponse = json_decode($this->response->getBody(), true);

		foreach ($expectedValues as $value) {
			if ((string)$realResponse[$value['key']] !== (string)$value['value']) {
				throw new InvalidArgumentException(
					sprintf(
						'Expected %s for key %s got %s',
						(string)$value['value'],
						$value['key'],
						(string)$realResponse[$value['key']]
					)
				);
			}
		}
	}


}
