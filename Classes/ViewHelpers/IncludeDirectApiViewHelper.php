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
 * View helper which allows 
 *
 * = Examples =
 *
 * <mvcextjs:be.moduleContainer pageTitle="foo" enableJumpToUrl="false" enableClickMenu="false" loadPrototype="false" loadScriptaculous="false" scriptaculousModule="someModule,someOtherModule" loadExtJs="true" loadExtJsTheme="false" extJsAdapter="jQuery" enableExtJsDebug="true" addCssFile="{f:uri.resource(path:'styles/backend.css')}" addJsFile="{f:uri.resource('scripts/main.js')}">
 * 	<mvcextjs:includeDirectApi />
 * </mvcextjs:be.moduleContainer>
 *
 * @category    ViewHelpers
 * @package     TYPO3
 * @subpackage  tx_mvcextjs
 * @author      Dennis Ahrens <dennis.ahrens@fh-hannover.de>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id: IncludeInlineJsFromFileViewHelper.php 30242 2010-02-20 14:32:48Z xperseguers $
 */
class Tx_MvcExtjs_ViewHelpers_IncludeDirectApiViewHelper extends Tx_MvcExtjs_ViewHelpers_AbstractViewHelper {
	
	/**
	 * @var Tx_MvcExtjs_ExtJS_Direct_API
	 */
	protected $api;
	
	/**
	 * @see Classes/ViewHelpers/Tx_MvcExtjs_ViewHelpers_AbstractViewHelper#initialize()
	 */
	public function initialize() {
		parent::initialize();
		$this->api = t3lib_div::makeInstance('Tx_MvcExtjs_ExtJS_Direct_API');
	}
	
	/**
	 * Generates a Ext.Direct API descriptor and adds it to the pagerenderer.
	 * Also calls Ext.Direct.addProvider() on itself (at js side).
	 * The remote API is directly useable.
	 * 
	 * @param string $name The name for the javascript variable.
	 * @param string $namespace The namespace the variable is placed.
	 * @param array $controllers The controllers that should be parsed.
	 * @param string $controllerExtensionName The extension name where the controllers are located.
	 * @param string $routeAction The name of the action that does the routing for Ext.Direct requests. Defaults: route.
	 * @param string $routeController The name of the controller that does the routing for Ext.Direct requests. Defaults: the controller that renders the Ext.Direct API (uses this ViewHelper).
	 * @param string $routeExtensionName The name of the extension that does the routing for Ext.Direct requests. Defaults: the extension that renders the Ext.Direct API (uses this ViewHelper).
	 * @param string $routeUrl You can specify a URL instead of using $routeAction, $routeController and $routeExtensionName.
	 * @param string $remoteAnnotation The annotation above the actions that should be accessable via this API.
	 * @param string $remoteNameAnnotation The annotation used to give actions other names on js side.
	 * @param string $actionFormat The format that is set for the route action when doing requests. Defaults: json.
	 * 
	 * @return void
	 */
	public function render($name = 'remoteDescriptor',
						   $namespace = 'Ext.ux.TYPO3.app',
						   array $controllers = NULL,
						   $controllerExtensionName = NULL,
						   $routeAction = 'route',
						   $routeController = NULL,
						   $routeExtensionName = NULL,
						   $routeUrl = NULL,
						   $remoteAnnotation = '@extdirect',
						   $remoteNameAnnotation = '@remoteName',
						   $actionFormat = 'json'
						   ) {
		session_start();
			// prepare output variable
		$jsCode = '';
			// prepare configuration parameters
		if ($routeController === NULL) {
			$routeController = $this->controllerContext->getRequest()->getControllerName();	
		}
		if ($routeExtensionName === NULL) {
			$routeExtensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		}
		if ($routeUrl === NULL) {
			$routeUrl = $this->controllerContext->getUriBuilder()->reset()->uriFor($routeAction,array('format' => $actionFormat),$routeController,$routeExtensionName);
		}
		if ($controllerExtensionName === NULL) {
			$controllerExtensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
			$controllerExtensionKey = $this->controllerContext->getRequest()->getControllerExtensionKey();
		}
		$descriptor = $namespace . '.' . $name;
		$controllerBasePath = t3lib_extMgm::extPath($controllerExtensionKey) . '/Classes/Controller/';
	
		if ($controllers === NULL) {
			$controllers = array();
			$controllerDirectory = dir($controllerBasePath);
			while (FALSE !== ($controllerFileName = $controllerDirectory->read())) {
				if (substr($controllerFileName,0,1) !== '.') {
					$controllerName = str_replace('.php','',$controllerFileName);
					$controllers[] = $controllerName;
				}
			}
		}
		
		$controllerPrefix = 'Tx_' . $controllerExtensionName . '_Controller_';
			// fetch api descriptor array
		$apiArray = $this->getApi($controllers,$routeUrl,$namespace,$descriptor,$remoteAnnotation,$remoteNameAnnotation,$controllerBasePath,$controllerPrefix);
			// build up the output
		$jsCode .= 'Ext.ns(\'' . $namespace . '\'); ' . "\n";
		$jsCode .= $descriptor . ' = ';
        $jsCode .= json_encode($apiArray);
        $jsCode .= ";\n";
        $jsCode .= 'Ext.Direct.addProvider(' . $descriptor . ');' . "\n";
        	// add the output to the pageRenderer
        $this->pageRenderer->addExtOnReadyCode($jsCode,TRUE);
        //$this->pageRenderer->addJsInlineCode($descriptor . ' written with ' . get_class($this) . ' at ' . microtime(),$jsCode);
        $_SESSION['ext-direct-state'] = $this->api->getState();
	}
	
	/**
	 * Sets neccessary informations for the Ext.Direct API class and gives back
	 * an array with the remoteDescriptor information.
	 * 
	 * @param array $controllers
	 * @param string $routeUrl
	 * @param string $namespace
	 * @param string $descriptor
	 * @param string $remoteAnnotation
	 * @param string $controllerBasePath
	 * @param string $classPrefix
	 * @return array
	 */
	private function getApi($controllers,$routeUrl,$namespace,$descriptor,$remoteAnnotation,$remoteNameAnnotation,$controllerBasePath,$classPrefix) {
		$this->api->setRouterUrl($routeUrl);
		$this->api->setNamespace($namespace);
		$this->api->setDescriptor($descriptor);
		$this->api->setRemoteAttribute($remoteAnnotation);
		$this->api->setNameAttribute($remoteNameAnnotation);
		$this->api->setDefaults(array(
		    'basePath' => $controllerBasePath
		));
		$this->api->add(
		    $controllers,
		    array(
		    	'prefix' => $classPrefix
		    )
		);
		return $this->api->output(FALSE);
	}

}

?>