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
 * View helper which allows you to set up a columnDefinition.
 * F.e. a GridPanel based on a domain model.
 * 
 * Note: This feature is experimental!
 * Note: You MUST wrap this Helper with <mvcextjs:be.moduleContainer>-Tags
 *
 * = Examples =
 *
 * <mvcextjs:be.moduleContainer pageTitle="foo" enableJumpToUrl="false" enableClickMenu="false" loadPrototype="false" loadScriptaculous="false" scriptaculousModule="someModule,someOtherModule" loadExtJs="true" loadExtJsTheme="false" extJsAdapter="jQuery" enableExtJsDebug="true" addCssFile="{f:uri.resource(path:'styles/backend.css')}" addJsFile="{f:uri.resource('scripts/main.js')}">
 * 	<mvcextjs:includeColumnDefinition />
 * </mvcextjs:be.moduleContainer>
 *
 * @category    ViewHelpers
 * @package     TYPO3
 * @subpackage  tx_mvcextjs
 * @author      Dennis Ahrens <dennis.ahrens@fh-hannover.de>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id: ColumnDefinitionViewHelper.php 30482 2010-02-25 14:58:49Z deaddivinity $
 */
class Tx_MvcExtjs_ViewHelpers_JsCode_ConfigViewHelper extends Tx_MvcExtjs_ViewHelpers_JsCode_AbstractJavaScriptCodeViewHelper {

	/**
	 * The variable as js object that represents the returned column definition
	 * 
	 * @var Tx_MvcExtjs_CodeGeneration_JavaScript_Variable
	 */
	protected $configVariable;
	
	/**
	 * Initializes the ViewHelper
	 * 
	 * @see Classes/ViewHelpers/Be/Tx_MvcExtjs_ViewHelpers_Be_AbstractJavaScriptCodeViewHelper#initialize()
	 */
	public function initialize() {
		parent::initialize();
		$this->configVariable = t3lib_div::makeInstance('Tx_MvcExtjs_CodeGeneration_JavaScript_Variable');
	}
	
	/**
	 * Renders the JS code for a ExtJS config object
	 * 
	 * @param string $name The name for the config variable.
	 * @param array $parameters The parameters for the config object
	 * @param array $rawParameters The parameters for the config object
	 * @return void
	 */
	public function render($name = 'UnknownObject',
						   array $parameters = array(),
						   array $rawParameters = array()) {

			// Create the js object
		$configObject = t3lib_div::makeInstance('Tx_MvcExtjs_CodeGeneration_JavaScript_ExtJS_Config');
		
		foreach ($parameters as $parameter => $value) {
			$configObject->set($parameter,$value);
		}
		foreach ($rawParameters as $parameter => $value) {
			$configObject->setRaw($parameter,$value);
		}
		$this->configVariable->setName($this->extJsNamespace . '.' . $name);
		$this->configVariable->setValue($configObject);

		$this->injectJsCode();
	}
	
	/**
	 * @see Classes/ViewHelpers/JsCode/Tx_MvcExtjs_ViewHelpers_JsCode_AbstractJavaScriptCodeViewHelper#injectJsCode()
	 */
	protected function injectJsCode() {
			// Allow objects to be declared inside this viewhelper; they are rendered above
		$this->renderChildren();
			// Add the code and write it into the inline section in your HTML head
		$this->jsCode->addSnippet($this->configVariable); 
		parent::injectJsCode();
	}

}
?>