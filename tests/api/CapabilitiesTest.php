<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Tests\API;

use PHPUnit\Framework\TestCase;

class CapabilitiesTest extends TestCase {
	protected \GuzzleHttp\Client $http;

	protected function setUp() : void {
		$this->http = new \GuzzleHttp\Client([
			'base_uri' => 'http://localhost:8080/',
			'auth' => ['test', 'test'],
			'http_errors' => false,
			'headers' => ['Accept' => 'application/json'],
		]);
	}

	public function testCapabilities() {
		$response = $this->http->request('GET', 'ocs/v2.php/cloud/capabilities', [
			'headers' => [
				'OCS-APIRequest' => 'true',
			]
		]);
		$this->assertEquals(200, $response->getStatusCode(), 'Response status code');
		$this->assertTrue(
			$response->hasHeader('Content-Type'),
			'Response has content-type header'
		);
		$this->assertEquals(
			'application/json; charset=utf-8',
			$response->getHeaderLine('Content-Type'),
			'Response content type'
		);
		$ocs = json_decode($response->getBody()->getContents());
		$capabilities = $ocs->ocs->data->capabilities;
		$this->assertObjectHasAttribute('notes', $capabilities, 'Nextcloud provides capabilities');
		$notesCapability = $capabilities->notes;
		$this->assertObjectHasAttribute('api_version', $notesCapability, 'Notes API-Version capability exists');
		$apiVersions = $notesCapability->api_version;
		$this->assertIsArray($apiVersions, 'Notes API-Version capability is array');
		$this->assertNotEmpty($apiVersions, 'Notes API-Version capability array');
		foreach ($apiVersions as $apiVersion) {
			$this->assertStringMatchesFormat('%d.%d', $apiVersion, 'API Version format');
			$v = $apiVersion === '0.2' ? '02' : intval($apiVersion);
			$path = dirname(__FILE__) . '/APIv' . $v . 'Test.php';
			$this->assertFileExists($path, 'Test for API v' . $apiVersion . ' exists');
		}
	}

	public function testInvalidVersion() {
		$v = 7;
		$response1 = $this->http->request('GET', 'index.php/apps/notes/api/v' . $v . '/notes');
		$this->assertEquals(400, $response1->getStatusCode(), 'First response status code');
		$response2 = $this->http->request('GET', 'index.php/apps/notes/api/v' . $v . '/notes/1');
		$this->assertEquals(400, $response2->getStatusCode(), 'Second response status code');
	}
}
