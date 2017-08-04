<?php

/**
 * PHP version 7.0.14
 *
 * @package Irkagentanet\Conveyor
 */

namespace Irkagentanet;

use \DOMDocument;
use \Irkagentanet\Conveyor;
use \PHPUnit_Framework_TestCase;

/**
 * Test for conveyor
 *
 * @author    Andrey Mashukov <a.mashukov@yandex.ru>
 * @copyright 2013-2016 Andrey Mashukov
 * @license   https://opensource.org/licenses/MIT MIT License
 * @version   SVN: $Date$ $Revision$
 * @link      $HeadURL$
 *
 * @runTestsInSeparateProcesses
 *
 * @donottranslate
 */

class ConveyorTest extends PHPUnit_Framework_TestCase
    {

	/**
	 * Prepare to test
	 *
	 * @return void
	 */

	protected function setUp()
	    {
		define("CONVEYOR_DIR", __DIR__ . "/testdir");
	    } //end setUp()


	/**
	 * Tears down
	 *
	 * @return void
	 */

	protected function tearDown()
	    {
		$conveyor = new Conveyor(__DIR__ . "/AdSchema.xsd");
		$conveyor->clear();
	    } //end tearDown()


	/**
	 * Should allow to add, get and remove elements
	 *
	 * @return void
	 */

	public function testShouldAllowToAddGetAndRemoveElements()
	    {
		$conveyor = new Conveyor(__DIR__ . "/AdSchema.xsd");
		$doc      = new DOMDocument("1.0", "utf-8");
		$doc->load(__DIR__ . "/example.xml");
		$time = time();
		$conveyor->add($doc, "sender");
		$elements = $conveyor->getAllBySender("sender");
		$this->assertEquals($doc->saveXML(), $elements[0]["content"]);
		$this->assertEquals("sender", $elements[0]["sender"]);
		$this->assertEquals($time, $elements[0]["time"]);
		$conveyor->remove($elements[0]);
		$elements = $conveyor->getAllBySender("sender");
		$this->assertEquals(array(), $elements);
	    } //end testShouldAllowToAddGetAndRemoveElements()


	/**
	 * Should return count of elements in conveyor by sender
	 *
	 * @return void
	 */

	public function testShouldReturnCountOfElementsInConveyorBySender()
	    {
		$conveyor = new Conveyor(__DIR__ . "/AdSchema.xsd");
		$doc      = new DOMDocument("1.0", "utf-8");
		$doc->load(__DIR__ . "/example.xml");
		$n = 100;
		for ($i = 0; $i < $n; $i++)
		    {
			$conveyor->add($doc, "sender");
		    }
		$this->assertEquals($n, $conveyor->count("sender"));
	    } //end testShouldReturnCountOfElementsInConveyorBySender()


	/**
	 * Should move element to next service (sender)
	 *
	 * @return void
	 */

	public function testShouldMoveElementToNextService()
	    {
		$conveyor = new Conveyor(__DIR__ . "/AdSchema.xsd");
		$doc      = new DOMDocument("1.0", "utf-8");
		$doc->load(__DIR__ . "/example.xml");
		$n = 10;
		for ($i = 0; $i < $n; $i++)
		    {
			$conveyor->add($doc, "sender");
		    }
		$elements = $conveyor->getAllBySender("sender");
		foreach ($elements as $element)
		    {
			$conveyor->next($element, "nextservice");
		    }
		$this->assertEquals($n, $conveyor->count("nextservice"));
	    } //end testShouldMoveElementToNextService()


	/**
	 * Should allow to move any count of elements to another sender
	 *
	 * @return void
	 */

	public function testShouldAllowToMoveAnyCountOfElementsToAnotherSender()
	    {
		$conveyor = new Conveyor(__DIR__ . "/AdSchema.xsd");
		$doc      = new DOMDocument("1.0", "utf-8");
		$doc->load(__DIR__ . "/example.xml");
		$n = 10;
		for ($i = 0; $i < $n; $i++)
		    {
			$conveyor->add($doc, "sender");
		    }
		$elements = $conveyor->getAllBySender("sender");
		$conveyor->sendTo("sender", "nextservice", 5);
		$this->assertEquals(5, $conveyor->count("nextservice"));
		$this->assertEquals(5, $conveyor->count("sender"));
	    } //end testShouldAllowToMoveAnyCountOfElementsToAnotherSender()


    } //end class

?>
