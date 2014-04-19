<?php
/**
 * ownCloud - Notes
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Bernhard Posselt 2012, 2014
 */

namespace OCA\Notes\Utility;

class MethodAnnotationReaderTest extends \PHPUnit_Framework_TestCase {


	/**
	 * @Annotation
	 */
	public function testReadAnnotation(){
		$reader = new MethodAnnotationReader('\OCA\Notes\Utility\MethodAnnotationReaderTest',
				'testReadAnnotation');

		$this->assertTrue($reader->hasAnnotation('Annotation'));
	}


	/**
	 * @Annotation
	 * @param test
	 */
	public function testReadAnnotationNoLowercase(){
		$reader = new MethodAnnotationReader('\OCA\Notes\Utility\MethodAnnotationReaderTest',
				'testReadAnnotationNoLowercase');

		$this->assertTrue($reader->hasAnnotation('Annotation'));
		$this->assertFalse($reader->hasAnnotation('param'));
	}


}