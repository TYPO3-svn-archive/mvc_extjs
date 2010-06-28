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
 * @package mvcExtjs
 * @version $ID:$
 */
class Tx_MvcExtjs_DirectDispatcher extends Tx_Extbase_Dispatcher {
	
	/**
	 * @var Tx_Extbase_Configuration_AbstractConfigurationManager
	 */
	protected static $configurationManager;
	
	/**
	 * The configuration for the Extbase framework
	 * @var array
	 */
	protected static $extbaseFrameworkConfiguration;
	
	/**
	 * Initializes the configuration manager and the Extbase settings
	 *
	 * @param $configuration The current incoming configuration
	 * @return void
	 */
	protected function initializeConfigurationManagerAndFrameworkConfiguration($configuration) {
		if (TYPO3_MODE === 'FE') {
			self::$configurationManager = t3lib_div::makeInstance('Tx_Extbase_Configuration_FrontendConfigurationManager');
			self::$configurationManager->setContentObject($this->cObj);
		} else {
			self::$configurationManager = t3lib_div::makeInstance('Tx_Extbase_Configuration_BackendConfigurationManager');
		}
		self::$extbaseFrameworkConfiguration = self::$configurationManager->getFrameworkConfiguration($configuration);
	}
	
	/**
	 * Returns the ReflectionService
	 * 
	 * @return Tx_Extbase_Reflection_Service
	 */
	public static function getReflectionService() {
		return self::$reflectionService;
	}
	
	/**
	 * Misemployed method to get the DirectDispatcher working for the Backend.
	 * This behaviour has to be changed - GET parameter M should be the calling module.
	 * 
	 * TODO: make this available for FE calls.
	 *
	 * @param string $module The name of the module
	 * @return void
	 */
	public function callModule($module) {
		if ($module === 'MvcExtjsDirectDispatcher') {
			$this->timeTrackPush('MvcExtjs DirectDispatcher is called.','');
			$this->timeTrackPush('Extbase gets initialized.','');
			echo $this->parseRequest();
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Creates a request an dispatches it to a controller.
	 *
	 * @param string $content The content
	 * @param array $configuration The TS configuration array
	 * @return string $content The processed content
	 */
	public function dispatch($content, $configuration) {
		if (!is_array($configuration)) {
			t3lib_div::sysLog('Extbase was not able to dispatch the request. No configuration.', 'extbase', t3lib_div::SYSLOG_SEVERITY_ERROR);
			return $content;
		}
		
		$this->initializeConfigurationManagerAndFrameworkConfiguration($configuration);
		
			// framework configuration is normally fetched from the Dispatcher object.
			// let's make it available when this dispatcher acts.
		$normalDispatcher = t3lib_div::makeInstance('Tx_Extbase_Dispatcher');
		$normalDispatcher->initializeConfigurationManagerAndFrameworkConfiguration($configuration);

		$requestBuilder = t3lib_div::makeInstance('Tx_MvcExtjs_MVC_Web_DirectRequestBuilder');
		$requestBuilder->initialize(self::$extbaseFrameworkConfiguration,self::$reflectionService);
		
		$request = $requestBuilder->build($configuration['directRequestData']);
		
		if (isset($this->cObj->data) && is_array($this->cObj->data)) {
				// we need to check the above conditions as cObj is not available in Backend.
			$request->setContentObjectData($this->cObj->data);
			$request->setIsCached($this->cObj->getUserObjectType() == tslib_cObj::OBJECTTYPE_USER);
		}
		
		$response = t3lib_div::makeInstance('Tx_MvcExtjs_MVC_Web_DirectResponse',$request);

		$this->timeTrackPull();
		$this->timeTrackPush('Extbase dispatches request.','');
		
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
	 * Parses the request like it is done in the Router.php
	 * contained in the PHP implementation of Tommy Maintz.
	 * 
	 * TODO: FE compatibility (BACKPATH)
	 * 
	 * @return array
	 */
	protected function parseRequest() {	
		$configuration = $this->resolveModuleConfiguration();
		$requests = array();
		if (isset($GLOBALS['HTTP_RAW_POST_DATA'])){
			$requests = json_decode($GLOBALS['HTTP_RAW_POST_DATA'],TRUE);
			if (isset($requests['action']) && isset($requests['method']) && isset($requests['tid']) ) {
				$requests = array($requests);
			}
		} else if (isset($_POST['extAction'])){ // form post
			$requests[0]['action'] = $_POST['extAction'];
			$requests[0]['method'] = $_POST['extMethod'];
			$requests[0]['tid'] = $_POST['extTID'];
			$requests[0]['data'] = array($_POST, $_FILES);
		} else {
				// other kinds of rpc's may be handled here
			throw new Tx_MvcExtjs_ExtJS_Exception('Invalid RPC format',1277309575);
		}
			// BACK_PATH is the path from the typo3/ directory from within the
			// directory containing the controller file. We are using mod.php dispatcher
			// and thus we are already within typo3/ because we call typo3/mod.php
		$GLOBALS['BACK_PATH'] = '';
		return $this->processRequests($requests,$configuration);
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
	protected function processRequests($requests,$configuration) {
		$response = t3lib_div::makeInstance('Tx_Extbase_MVC_Web_Response');
		$response->setHeader('Content-Type','text/javascript');
		
		$persistenceManager = self::getPersistenceManager();
		
		$responses = array(); 
		foreach ($requests as $requestNumber => $requestData) {
			$configuration['directRequestData'] = $requestData;
			$responses[] = $this->dispatch('',$configuration);
		}
		
		if (count($responses) !== 1) {
			$response->setContent('[' . implode(',',$responses) . ']');
		} else {
			$response->setContent($responses[0]);
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

		if (count($response->getAdditionalHeaderData()) > 0) {
			$GLOBALS['TSFE']->additionalHeaderData[$request->getControllerExtensionName()] = implode("\n", $response->getAdditionalHeaderData());
		}
		$this->timeTrackPull();
		return $response->getContent();
	}
	
	/**
	 * Resolves the module configuration from TypoScript.
	 * The module has to be given as GET/POST parameter called tx_mvcextjs_dispatcher[module].
	 * 
	 * Does not cares abouot controller/action combination, because we may call more than one action.
	 * Ext.Direct sometimes combines several Ext.Direct requests in one HTTP request. 
	 *
	 * @return array
	 */
	protected function resolveModuleConfiguration() {
		$argumentPrefix = strtolower('tx_mvcextjs_dispatcher');
		$dispatcherParameters = t3lib_div::_GPmerged($argumentPrefix);
		$moduleName = $dispatcherParameters['module'];
		
		$config = $GLOBALS['TBE_MODULES']['_configuration'][$moduleName];

		$extbaseConfiguration = array(
			'userFunc' => 'tx_mvcextjs_directdispatcher->dispatch',
			'pluginName' => $moduleName,
			'extensionName' => $config['extensionName'],
			'controller' => 'Default',
			'action' => 'index',
			'switchableControllerActions.' => array(),
			'settings' => '< module.tx_' . strtolower($config['extensionName']) . '.settings',
			'persistence' => '< module.tx_' . strtolower($config['extensionName']) . '.persistence',
			'view' => '< module.tx_' . strtolower($config['extensionName']) . '.view',
		);
		$i = 1;
		foreach ($config['controllerActions'] as $controller => $actions) {
			$extbaseConfiguration['switchableControllerActions.'][$i++ . '.'] = array(
				'controller' => $controller,
				'actions' => $actions,
			);
		}
		return $extbaseConfiguration;
	}

}
?>