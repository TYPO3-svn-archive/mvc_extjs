<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Dennis Ahrens <dennis.ahrens@fh-hannover.de>
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
 * View helper which allows you to include an ExtJS Store based on the object notation
 * of a domain model
 * Note: This feature is experimental!
 * Note: You MUST wrap this Helper with <mvcextjs:be.moduleContainer>-Tags
 *
 * = Examples =
 *
 * <mvcextjs:be.moduleContainer pageTitle="foo" enableJumpToUrl="false" enableClickMenu="false" loadPrototype="true" loadScriptaculous="false" loadExtJs="true" loadExtJsTheme="false" extJsAdapter="prototype" enableExtJsDebug="true">
 * 	<mvcextjs:Be.IncludeStore domainModel="yourModelName" actions="{read:'yourActionForFetchingTheRecords',update:'yourActionForUpdatingRecords'}" controller="yourController" extensionName="yourExtensionName" />
 * </mvcextjs:be.moduleContainer>
 *
 * @category    ViewHelpers
 * @package     TYPO3
 * @subpackage  tx_mvcextjs
 * @author      Dennis Ahrens <dennis.ahrens@fh-hannover.de>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id: JsonWriterViewHelper.php 30242 2010-02-20 14:32:48Z xperseguers $
 */
class Tx_MvcExtjs_ViewHelpers_JsCode_DataWriterViewHelper extends Tx_MvcExtjs_ViewHelpers_JsCode_AbstractJavaScriptCodeViewHelper {

	/**
	 * @var Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_ExtendClass
	 */
	protected $writer;

	/**
	 * @var Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_Config
	 */
	protected $config;

	/**
	 * Initializes the ViewHelper
	 * 
	 * @see Classes/ViewHelpers/Be/Tx_MvcExtjs_ViewHelpers_Be_AbstractJavaScriptCodeViewHelper#initialize()
	 */
	public function initialize() {
		parent::initialize();
		$this->config = new Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_Config();
		$this->writer = new Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_ExtendClass(
			'defaultJsonReaderName',
			'Ext.ux.TYPO3.MvcExtjs.DataWriter',
			array(),
			$this->config,
			new Tx_MvcExtjs_CodeGeneration_JavaScript_Object(),
			$this->extJsNamespace
		);
	}

	/**
	 * Renders the Code for a JsonReader build up on the data given by the domainModel
	 * 
	 * @param string $domainModel is used as variable name AND storeId for the generated store
	 * @param string $extensionName the EXT where the domainModel is located
	 * @param string $name choose a id for the created variable; default is $domainmodel . 'JsonReader'
	 * @param string $objectName
	 * @param string $moduleName
	 * @return void
	 */
	public function render($domainModel = NULL,
						   $extensionName = NULL,
						   $name = NULL,
						   $objectName = NULL,
						   $moduleName = NULL) {
				   	
		if ($extensionName === NULL) {
			$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		}

		$domainClassName = 'Tx_' . $extensionName . '_Domain_Model_' . $domainModel;
			// Check if the given domain model class exists
		if (!class_exists($domainClassName)) {
			throw new Tx_Fluid_Exception('The Domain Model Class (' . $domainClassName . ') for the given domainModel (' . $domainModel . ') was not found', 1264069568);
		}
			// Build up and set the for the JS store variable
		if ($name === NULL) {
			$name = $domainModel . 'DataWriter';
		}
		if ($objectName === NULL) {
			$objectName = $domainModel;
			$objectName{0} = strtolower($objectName{0});
		}
		if ($moduleName === NULL) {
			throw new Tx_MvcExtjs_ExtJS_Exception('If no module name is given the object cannot be mapped by extbase!',1269335971);
		}
		$extKey = 'tx_' . strtolower($extensionName);
		
		$this->writer->setName($name);
			// Read the given config parameters into the Extjs Config Object
		$this->config->set('objectName', $objectName)
					 ->set('moduleName', $moduleName)
					 ->set('extKey',$extKey);
			// render the code
		$this->injectJsCode();
	}

	/**
	 * (non-PHPdoc)
	 * @see Classes/ViewHelpers/JsCode/Tx_MvcExtjs_ViewHelpers_JsCode_AbstractJavaScriptCodeViewHelper#injectJsCode()
	 */
	public function injectJsCode() {
			// Apply the configuration again
		$this->writer->setConfig($this->config);
			// Allow objects to be declared inside this viewhelper; they are rendered above
		$this->renderChildren();
			// Add the code and write it into the inline section in your HTML head
		//$this->jsCode->addSnippet(new Tx_MvcExtjs_CodeGeneration_JavaScript_Snippet('Ext.ns("Ext.ux.TYPO3.MvcExtjs");'));
		$this->jsCode->addSnippet($this->writer);
		parent::injectJsCode();
	}

}
?>