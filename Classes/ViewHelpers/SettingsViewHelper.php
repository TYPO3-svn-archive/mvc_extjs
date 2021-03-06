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
 * 
 * = Examples =
 *
 * <mvcextjs:be.moduleContainer pageTitle="foo" enableJumpToUrl="false" enableClickMenu="false" loadPrototype="false" loadScriptaculous="false" scriptaculousModule="someModule,someOtherModule" loadExtJs="true" loadExtJsTheme="false" extJsAdapter="jQuery" enableExtJsDebug="true" addCssFile="{f:uri.resource(path:'styles/backend.css')}" addJsFile="{f:uri.resource('scripts/main.js')}">
 * 	<mvcextjs:settings settings="{settings} />
 * </mvcextjs:be.moduleContainer>
 *
 * @category    ViewHelpers
 * @package     TYPO3
 * @subpackage  tx_mvcextjs
 * @author      Dennis Ahrens <dennis.ahrens@fh-hannover.de>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id: ColumnDefinitionViewHelper.php 30482 2010-02-25 14:58:49Z deaddivinity $
 */
class Tx_MvcExtjs_ViewHelpers_SettingsViewHelper extends Tx_MvcExtjs_ViewHelpers_AbstractViewHelper {

	/**
	 * Renders the JS code that makes a global settings variable available for the namespace of the module/plugin.
	 * 
	 * @param string $name is used as variable name
	 * @param array $settings a array containing settings for js
	 * @param string $namespace
	 * @return void
	 */
	public function render($name = 'Settings', array $settings = array(), $namespace='Ext.ux.TYPO3.app') {
		$jsCode = 'Ext.ns(\'' . $namespace . '\');' . "\n"; 
		$jsCode .= $namespace . '.' . $name . '=' . json_encode($settings);
		$this->pageRenderer->addJsInlineCode('Written by ' . get_class($this) . ' at ' . microtime(),$jsCode);
	}

}
?>