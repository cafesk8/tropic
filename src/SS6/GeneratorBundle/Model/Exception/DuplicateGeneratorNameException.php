<?php

namespace SS6\GeneratorBundle\Model\Exception;

use Exception;

class DuplicateGeneratorNameException extends Exception implements GeneratorException {

	/**
	 * @param string $name
	 * @param Exception $previous
	 */
	public function __construct($name, Exception $previous = null) {
		$message = 'Generator with name "' . $name . '" already exists';
		parent::__construct($message, 0, $previous);
	}

}
