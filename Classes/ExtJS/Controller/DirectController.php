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
 * A action controller to use when using ExtJS and Ext.Direct.
 *
 * @category    Controller
 * @package     TYPO3
 * @subpackage  tx_mvcextjs
 * @author      Dennis Ahrens <dennis.ahrens@fh-hannover.de>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class Tx_MvcExtjs_ExtJS_Controller_DirectController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * Acts as a dispatcher for Ext.Direct requests.
	 * 
	 * @return void
	 * @see getForwardArguments()
	 * @dontverifyrequesthash
	 */
	public function routeAction() {
		session_start();
		$apiState = $_SESSION['ext-direct-state'];
		$rpcData = $this->parseRequest();
			// process the rpcData to forward to the correct action
		$action = $this->getForwardActionName($rpcData,$apiState);
		$controller = $this->getForwardControllerName($rpcData,$apiState);
		$extensionName = $this->request->getControllerExtensionName();
		$arguments = $this->getForwardArguments($rpcData,$apiState,$action,$controller,$extensionName);
			// set the response' content-type
		$this->response->setHeader('Content-Type','text/javascript');
			// forward to the correct controller/action
		$this->forward($action,$controller,$extensionName,$arguments);
	}
	
	/**
	 * Parses the request like it is done in the Router.php
	 * contained in the PHP implementation of Tommy Maintz.
	 * 
	 * @return array
	 */
	protected function parseRequest() {
		$rpcData = array();
		if (isset($GLOBALS['HTTP_RAW_POST_DATA'])){
			$rpcData = json_decode($GLOBALS['HTTP_RAW_POST_DATA'],TRUE);
		} else if (isset($_POST['extAction'])){ // form post
		$rpcData['action'] = $_POST['extAction'];
		$rpcData['method'] = $_POST['extMethod'];
		$rpcData['tid'] = $_POST['extTID'];
		$rpcData['data'] = array($_POST, $_FILES);
		} else {
			throw new Tx_MvcExtjs_ExtJS_Exception('Invalid RPC format',1277309575);
		}
		return $rpcData;
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	protected function detectMultipleRequests() {
		
	}
	
	/**
	 * Evaluates the action name to forward to.
	 * Removes the ending 'Action'.
	 * 
	 * @param array $rpcData
	 * @param array $apiState
	 * @return string
	 */
	private function getForwardActionName(array $rpcData, array $apiState) {
		$jsActionName = $rpcData['method'];
		$jsControllerName = $rpcData['action'];
		if (!is_array($apiState['parsedAPI']['actions'][$rpcData['action']])) {
			throw new Tx_MvcExtjs_ExtJS_Exception('The Controller ' . $jsControllerName . ' is not defined in the remoteDescriptor',1276002610);
		}
		foreach ($apiState['parsedAPI']['actions'][$rpcData['action']] as $actionConfiguration) {
			if ($actionConfiguration['name'] === $jsActionName) {
				if (isset($actionConfiguration['serverMethod'])) {
					return str_replace('Action','',$actionConfiguration['serverMethod']);
				} else {
					return str_replace('Action','',$actionConfiguration['name']);
				}
			}
		}
		throw new Tx_MvcExtjs_ExtJS_Exception('Can not evaluate the action to redirect to',1276002611);
	}
	
	/**
	 * Evaluates the controller name to forward to.
	 * Removes the ending 'Controller'.
	 * 
	 * @param array $rpcData
	 * @param array $apiState
	 * @return string
	 */
	private function getForwardControllerName(array $rpcData, array $apiState) {
		$jsControllerName = $rpcData['action'];
		if (!is_array($apiState['parsedAPI']['actions'][$rpcData['action']])) {
			throw new Tx_MvcExtjs_ExtJS_Exception('The Controller ' . $jsControllerName . ' is not defined in the remoteDescriptor',1276002611);
		}
		return str_replace('Controller','',$jsControllerName);
	}
	
	/**
	 * Prepares the arguments for the action to forward to.
	 * The mapping just prepares the rpc data into the fluid
	 * related structure. The action where we forward to initializes
	 * the action parameters as known from fluid later on.
	 * 
	 * TODO: modified objects are not handled by now.
	 * 
	 * @param array $rpcData The data received from the Ext.Direct request.
	 * @param array $apiState The Ext.Direct API definition as hold by the class Tx_MvcExtjs_ExtJS_Direct_API.
	 * @param string $actionName The action name to forward to. We need this to fetch the action method parameters.
	 * @param string $controllerName The controller name to forward to. We need this to fetch the action method parameters.
	 * @param string $extensionName The extension name to forward to. We need this to fetch the action method parameters.
	 * 
	 * @return array
	 */
	private function getForwardArguments(array $rpcData, array $apiState,  $actionName, $controllerName, $extensionName) {
		$arguments = array();
		$arguments['tid'] = $rpcData['tid'];
		
		$actionParameters = $this->reflectionService->getMethodParameters('Tx_' . $extensionName . '_Controller_' . $controllerName . 'Controller',$actionName. 'Action');
		
		foreach ($rpcData['data'] as $position => $parameterValue) {
			foreach ($actionParameters as $parameterName => $parameterConfiguration) {
				if ($parameterConfiguration['position'] === $position) {
					switch ($parameterConfiguration['type']) {
						case 'string':
						case 'int':
						case 'boolean':
						case 'array':
							$arguments[$parameterName] = $parameterValue;
							break;
						default:
							if ($parameterConfiguration['class'] !== '') {
								$arguments[$parameterName]['__identity'] = $parameterValue['uid'];
								break;
							}
							throw new Tx_MvcExtjs_ExtJS_Exception('parameter type not supported yet!',1276273198);
					}
				}
			}
		}
		return $arguments;
	}

}
?>