<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Dennis Ahrens <dennis.ahrens@googlemail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * JavaScript Code Snippet
 * Representing a JavaScript Variable
 *
 * @category    CodeGeneration_JavaScript
 * @package     TYPO3
 * @subpackage  tx_mvcextjs
 * @author      Dennis Ahrens <dennis.ahrens@googlemail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_ExtendClass extends Tx_MvcExtjs_CodeGeneration_JavaScript_Variable {
	
	/**
	 * 
	 * @var string
	 */
	protected $class;
	
	/**
	 * 
	 * @var Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_Config
	 */
	protected $config;
	
	/**
	 * 
	 * @var array
	 */
	protected $inlineDeclarations;
	
	/**
	 * !!!not implemented yet!!!
	 * 
	 * @var Tx_MvcExtjs_CodeGeneration_JavaScript_Object
	 */
	protected $additionalFunctions;
	
	/**
	 * the internal used AnonymFunction Object
	 * 
	 * @var Tx_MvcExtjs_CodeGeneration_JavaScript_FunctionDeclaration
	 */
	protected $constructorFunction;
	
	/**
	 * 
	 * @param string $name
	 * @param string $class
	 * @param array $inlineDeclarations
	 * @param Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_Config $config
	 * @param Tx_MvcExtjs_CodeGeneration_JavaScript_Object $additionalFunctions HAS NO EFFECT ATM
	 * @param mixed $namespace FALSE or string
	 */
	public function __construct($name = NULL,
								$class = NULL,
								array $inlineDeclarations = array(),
								Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_Config $config,
								Tx_MvcExtjs_CodeGeneration_JavaScript_Object $additionalFunctions = NULL,
								$namespace = FALSE) {
		
		foreach ($inlineDeclarations as $snippet)
			if (!$snippet instanceof Tx_MvcExtjs_CodeGeneration_JavaScript_SnippetInterface)
				throw new Tx_MvcExtjs_CodeGeneration_JavaScript_Exception('a inlinedeclaration for the has to implement Tx_MvcExtjs_CodeGeneration_JavaScript_SnippetInterface',1264859988);
		
		$this->class = $class;
		$this->config = $config;
		$this->inlineDeclarations = $inlineDeclarations;
		$this->additionalFunctions = $additionalFunctions;
		$this->constructorFunction = new Tx_MvcExtjs_CodeGeneration_JavaScript_FunctionDeclaration(array('config'),$this->inlineDeclarations,TRUE);
		
		parent::__construct($name,NULL,FALSE,$namespace);
	}
	
	/**
	 * sets the class that should be extended
	 * 
	 * @param string $class
	 * @return Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_ExtendClass
	 */
	public function setClass($class) {
		$this->class = $class;
		return $this;
	}
	
	/**
	 * adds a config parameter to the constructor of the object that should be extended
	 * 
	 * @param string $name
	 * @param string $value
	 * @return Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_ExtendClass
	 */
	public function addConfig($name,$value) {
		$this->config->set($name,$value);
		return $this;
	}
	
	/**
	 * 
	 * @param string $name
	 * @param mixed $value string or something that implements Tx_MvcExtjs_CodeGeneration_JavaScript_SnippetInterface
	 * @return Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_ExtendClass
	 */
	public function addRawConfig($name,$value) {
		$this->config->setRaw($name,$value);
		return $this;
	}
	
	/**
	 * sets a config object for the extend constructor
	 * @param $config
	 * @return Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_ExtendClass
	 */
	public function setConfig(Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_Config $config) {
		$this->config = $config;
		return $this;
	}
	
	/**
	 * gets the config object from the extend constructor
	 * @return Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_Config
	 */
	public function getConfig() {
		return $this->config;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Classes/CodeGeneration/JavaScript/Tx_MvcExtjs_CodeGeneration_JavaScript_Variable#build()
	 */
	public function build() {
		$configConstructor = new Tx_MvcExtjs_CodeGeneration_JavaScript_FunctionCall('Ext.apply',array($this->config));
		$configVariable = new Tx_MvcExtjs_CodeGeneration_JavaScript_Variable('config',$configConstructor);
				
		$this->inlineDeclarations[] = $configVariable;
		
		$superclassConstructorName = $this->namespace . '.' . $this->name . '.superclass.constructor.call';
		$superclassCall = new Tx_MvcExtjs_CodeGeneration_JavaScript_FunctionCall($superclassConstructorName,array(new Tx_MvcExtjs_CodeGeneration_JavaScript_Snippet('this'),new Tx_MvcExtjs_CodeGeneration_JavaScript_Snippet('config')));
		
		$this->inlineDeclarations[] = $superclassCall;

		$this->constructorFunction->setContent($this->inlineDeclarations);
		
		$configElements = array(
			new Tx_MvcExtjs_CodeGeneration_JavaScript_ObjectElement('constructor',$this->constructorFunction),
		);
		$extendParameters = array(
			new Tx_MvcExtjs_CodeGeneration_JavaScript_Snippet($this->class),
			new Tx_MvcExtjs_CodeGeneration_JavaScript_Object($configElements),
		);
		$this->setValue(new Tx_MvcExtjs_CodeGeneration_JavaScript_FunctionCall('Ext.extend',$extendParameters));
		return parent::build();
	}
	
}

?>