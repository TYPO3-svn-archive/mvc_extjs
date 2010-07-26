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
 * Creates a request an dispatches it to the controller which was specified
 * by TS Setup, Flexform and returns the content to the v4 framework.
 *
 * This class is the main entry point for Ext.Direct RPC's that should be handled
 * by extbase.
 *
 * @package MvcExtjs
 * @version $ID:$
 */
class Tx_MvcExtjs_Dispatcher extends Tx_Extbase_Dispatcher {
	
	/**
	 * @var Tx_MvcExtjs_MVC_ExtDirect_RequestBuilder
	 */
	protected $requestBuilder;
	
	/**
	 * @var array
	 */
	protected $directApi;
	/**
	 * Parses the request an dispatches it to the controllers.
	 *
	 * @param string $content The content
	 * @param array $configuration The TS configuration array
	 * @return string $content The processed content
	 */
	public function dispatch($content, $configuration) {
		return $this->parseRequest($configuration);
	}
	
	/**
	 * Parses the incoming request.
	 * 
	 * @param $configuration
	 * @return string
	 */
	public function parseRequest($configuration) {
		if ( (isset($GLOBALS['HTTP_RAW_POST_DATA']) && json_decode($GLOBALS['HTTP_RAW_POST_DATA']) !== NULL ) || isset($_POST['extAction'])) {
			return $this->parseDirectRequest($configuration);		
		} else {
				// may need a better detection
			return parent::dispatch($content,$configuration);
		}
	}
	
	
	
	/**
	 * Creates a request an dispatches it to a controller.
	 *
	 * @param Tx_Extbase_MVC_RequestInterface $request The request.
	 * @param array $configuration The TS configuration array
	 * @return string $content The processed content
	 */
	private function dispatchDirectRequest($request) {
			// framework configuration is normally fetched from the Dispatcher object.
			// let's make it available when this dispatcher acts.
			// TODO: neccessary? better solution?
		//$normalDispatcher = t3lib_div::makeInstance('Tx_Extbase_Dispatcher');
		//$normalDispatcher->initializeConfigurationManagerAndFrameworkConfiguration($configuration);
		
		$response = t3lib_div::makeInstance('Tx_MvcExtjs_MVC_ExtDirect_Response',$request);

		$this->timeTrackPull();
		$this->timeTrackPush('MvcExtjs dispatches Ext.Direct request.','');
		
		$dispatchLoopCount = 0;
		while (!$request->isDispatched()) {
			if ($dispatchLoopCount++ > 99) throw new Tx_Extbase_MVC_Exception_InfiniteLoop('Could not ultimately dispatch the request after '  . $dispatchLoopCount . ' iterations.', 1217839467);
				$controller = $this->getPreparedController($request);
			try {
				$controller->processRequest($request, $response);
			} catch (Tx_Extbase_MVC_Exception_StopAction $ignoredException) {
			}
		}
		return $response->getContent();
	}
	
	/**
	 * Processes the requests that came in.
	 * Ext.Direct is able to combine several Ext.Direct requests in one HTTP request.
	 * This method provides the feature to dispatch each incoming Ext.Direct request
	 * to it's corresponding action - collect the results and gives them back.
	 * 
	 * @param array $requests
	 * @param array $configuration
	 * @return string
	 */
	private function processDirectRequests($requests,$configuration) {	
		$response = t3lib_div::makeInstance('Tx_Extbase_MVC_Web_Response');
		$response->setHeader('Content-Type','text/javascript');
		
		$this->initializeConfigurationManagerAndFrameworkConfiguration($configuration);
		
		$this->requestBuilder = t3lib_div::makeInstance('Tx_MvcExtjs_MVC_ExtDirect_RequestBuilder');
		$this->requestBuilder->initialize(self::$extbaseFrameworkConfiguration);
		
		$directService = t3lib_div::makeInstance('Tx_MvcExtjs_ExtJS_DirectApi');
		$this->directApi = $directService->getApi('','',TRUE,FALSE);
		
		$persistenceManager = self::getPersistenceManager();
		
		$responseData = array(); 
		foreach ($requests as $requestNumber => $requestData) {
			$this->compareDirectRequestWithDirectApi($requestData); // throws Exception if something went wrong!
			$request = $this->requestBuilder->build($requestData);
			if (isset($this->cObj->data) && is_array($this->cObj->data)) {
					// we need to check the above conditions as cObj is not available in Backend.
				$request->setContentObjectData($this->cObj->data);
				$request->setIsCached($this->cObj->getUserObjectType() == tslib_cObj::OBJECTTYPE_USER);
			}
			$responseData[] = $this->dispatchDirectRequest($request);
		}
		
		if (count($responseData) !== 1) {
			$response->setContent('[' . implode(',',$responseData) . ']');
		} else {
			$response->setContent($responseData[0]);
		}

		$this->timeTrackPull();
		$this->timeTrackPush('Extbase persists all changes.','');
		
		$flashMessages = t3lib_div::makeInstance('Tx_Extbase_MVC_Controller_FlashMessages'); // singleton
		$flashMessages->persist();
		$persistenceManager->persistAll();
		$this->timeTrackPull();

		self::$reflectionService->shutdown();
		
		if (substr($response->getStatus(), 0, 3) === '303') {
			$response->sendHeaders();
			exit;
		}
		$response->sendHeaders();
		if (count($response->getAdditionalHeaderData()) > 0) {
			$GLOBALS['TSFE']->additionalHeaderData[$request->getControllerExtensionName()] = implode("\n", $response->getAdditionalHeaderData());
		}
		$this->timeTrackPull();
		return $response->getContent();
	}
	
	/**
	 * Makes sure, that the called action is included in the Ext.Direct API
	 * descriptor that was generated for the called module/plugin.
	 * 
	 * @param array $requestData
	 * @throws Tx_MvcExtjs_ExtJS_Exception
	 * @return void
	 */
	private function compareDirectRequestWithDirectApi(array $requestData) {
		$actionApi = $this->directApi['actions'][$requestData['action']];
		if (!isset($actionApi)) {
			throw new Tx_MvcExtjs_ExtJS_Exception('Ext.Direct has called a controller (' . $requestData['action'] . ') that is not included in the generated API. That should never happen.',1277804445);
		} else {
			$methodIsDescribed = FALSE;
			foreach ($actionApi as $methodApi) {
				if ($methodApi["name"] === $requestData['method']) {
					$methodIsDescribed = TRUE;
					break;
				}
			}
			if (!$methodIsDescribed) {
				throw new Tx_MvcExtjs_ExtJS_Exception('Ext.Direct has called a the action (' . $requestData['method'] . ') on controller (' . $requestData['action'] . ') that is not included in the generated API. That should never happen.',1277804445);
			}
		}
	}
	
	/**
	 * Parses the request like it is done in the Router.php
	 * contained in the Ext.Direct PHP implementation from Tommy Maintz.
	 * 
	 * @return array
	 */
	private function parseDirectRequest($configuration) {
		if (!is_array($configuration)) {
			t3lib_div::sysLog('MvcExtjs was not able to dispatch the request. No configuration.', 'MvcExtjs', t3lib_div::SYSLOG_SEVERITY_ERROR);
			return $content;
		}
		$requests = array();
		if (isset($GLOBALS['HTTP_RAW_POST_DATA'])){
			$requests = json_decode($GLOBALS['HTTP_RAW_POST_DATA'],TRUE);
			if (isset($requests['action']) && isset($requests['method']) && isset($requests['tid']) ) {
				$requests = array($requests);
			} else {
				
			}
		} else if (isset($_POST['extAction'])){ // form post
			$requests[0]['action'] = $_POST['extAction'];
			$requests[0]['method'] = $_POST['extMethod'];
			$requests[0]['tid'] = $_POST['extTID'];
			$requests[0]['data'] = array($_POST, $_FILES);
		} else {
			throw new Tx_MvcExtjs_ExtJS_Exception('Invalid RPC format',1277309575);
		}
		return $this->processDirectRequests($requests,$configuration);
	}

}
?>