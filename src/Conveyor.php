<?php

/**
 * PHP version 7.0.14
 *
 * @package Irkagentanet\Conveyor
 */

namespace Irkagentanet;

use \DirectoryIterator;
use \DOMDocument;

/**
 * Data conveyor (queue)
 *
 * @author    Andrey Mashukov <a.mashukov@yandex.ru>
 * @copyright 2013-2016 Andrey Mashukov
 * @license   https://opensource.org/licenses/MIT MIT License
 * @version   SVN: $Date$ $Revision$
 * @link      $HeadURL$
 */

class Conveyor
    {
	/**
	 * Data directory (path to storage)
	 *
	 * @var string
	 */
	private $_datadir;

	/**
	 * XML Schema for validate data
	 *
	 * @var string
	 */
	private $_schema;

	/**
	 * Construct class
	 *
	 * @param string $schema XML Schema
	 *
	 * @return void
	 */

	public function __construct($schema = false)
	    {
		$datadir = "/home/conveyor";
		if (file_exists($datadir) === false)
		    {
			mkdir($datadir);
		    }
		$this->_datadir = $datadir;

		$this->_schema = $schema;
	    } //end __construct()


	/**
	 * Add element to conveyor
	 *
	 * @param DOMDocument $doc    Element (XML file)
	 * @param string      $sender Sender name
	 *
	 * @return bool Result of operation
	 */

	public function add(DOMDocument $doc, $sender)
	    {
		if($doc->schemaValidate($this->_schema) === true)
		    {
			while (true)
			    {
				$to = $this->_datadir . "/" . $sender;
				if (file_exists($to) === false)
				    {
					mkdir($to);
				    }

				$data = array(
					 "id"      => md5(uniqid()),
					 "time"    => time(),
					 "sender"  => $sender,
					 "content" => $doc->saveXML(),
					);

				file_put_contents($to . "/" . $data["id"], gzencode(serialize($data)));

				$infile = unserialize(gzdecode(file_get_contents($to . "/" . $data["id"])));
				if ($infile === $data)
				    {
					break;
				    }
			    }

			return file_exists($to . "/" . $data["id"]);
		    }
		else
		    {
			return false;
		    } //end if
	    } //end add()


	/**
	 * Get count elements by sender name
	 *
	 * @param string $sender Sender name
	 *
	 * @return int Number of elements in conveyor
	 */

	public function count($sender)
	    {
		$count = 0;
		$from = $this->_datadir . "/" . $sender;
		if (file_exists($from) === false)
		    {
			return 0;
		    }

		foreach (new DirectoryIterator($from) as $fileInfo)
		    {
			if($fileInfo->isDot())
			    {
				continue;
			    }

			$count++;
		    }

		return $count;
	    } //end count()


	/**
	 * Get all elements by sender name
	 *
	 * @param string $sender Sender name
	 * @param int    $limit  Limit of elements
	 *
	 * @return array Elements from conveyor
	 */

	public function getAllBySender($sender, $limit = false)
	    {
		$all = array();
		if ($limit === false)
		    {
			$limit = 150;
		    }

		$from = $this->_datadir . "/" . $sender;
		if (file_exists($from) === false)
		    {
			mkdir($from);
		    }

		$n = 0;
		foreach (new DirectoryIterator($from) as $fileInfo)
		    {
			if($fileInfo->isDot() === false && (int) $fileInfo->getSize() !== 0 && $fileInfo->isDir() === false)
			    {
				$all[] = unserialize(gzdecode(file_get_contents($from . "/" . $fileInfo->getFilename())));
				$n++;
			    }
			else if ((int) $fileInfo->getSize() === 0)
			    {
				unlink($from . "/" . $fileInfo->getFilename());
			    }

			if ($n >= $limit)
			    {
				break;
			    }
		    }

		return $all;
	    } //end getAllBySender()


	/**
	 * Success processing element, element has been processed and should been removed
	 *
	 * @param array $element Element from conveyor
	 *
	 * @return bool Operation status
	 */

	public function success(array $element)
	    {
		return unlink($this->_datadir . "/" . $element["sender"] . "/" . $element["id"]);
	    } //end success()


	/**
	 * Move elements to other sender
	 *
	 * @param string $from  Owner of elements
	 * @param string $to    Recipient of elements
	 * @param int    $count Count of elements to need move
	 *
	 * @return void
	 */

	public function sendTo($from, $to, $count)
	    {
		$elements = $this->getAllBySender($from, $count);
		foreach ($elements as $data)
		    {
			$this->next($data, $to);
		    }
	    } //end sendTo()


	/**
	 * Move element to next service (other owner)
	 *
	 * @param array  $element Element
	 * @param string $sender  Name of new owner (sender)
	 *
	 * @return bool Operation status
	 */

	public function next(array $element, $sender)
	    {
		$to = $this->_datadir . "/" . $sender;
		if (file_exists($to) === false)
		    {
			mkdir($to);
		    }

		unlink($this->_datadir . "/" . $element["sender"] . "/" . $element["id"]);
		$element["sender"] = $sender;
		file_put_contents($to . "/" . $element["id"], gzencode(serialize($element)));
		return file_exists($to . "/" . $element["id"]);
	    } //end next()


	/**
	 * Remove element
	 *
	 * @param array $element Element
	 *
	 * @return bool Operation status
	 */

	public function remove($element)
	    {
		return unlink($this->_datadir . "/" . $element["sender"] . "/" . $element["id"]);
	    } //end remove()


	/**
	 * Clear storage (Remove all)
	 *
	 * @return void
	 */

	public function clear()
	    {
		foreach (new DirectoryIterator($this->_datadir) as $fileInfo)
		    {
			if($fileInfo->isDot())
			    {
				continue;
			    }

			$dir = $fileInfo->getFilename();

			foreach (new DirectoryIterator($this->_datadir . "/" . $dir) as $file)
			    {
				if($file->isDot())
				    {
					continue;
				    }

				unlink($this->_datadir . "/" . $dir . "/" . $file->getFilename());
			    }

			rmdir($this->_datadir . "/" . $dir);
		    }
	    } //end clear()

    } //end class

?>
