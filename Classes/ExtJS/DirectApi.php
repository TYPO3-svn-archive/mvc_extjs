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
 * A Service that provides the Ext.Direct Api
 *
 * @package     MvcExtjs
 * @subpackage  ExtJS
 * @author      Dennis Ahrens <dennis.ahrens@fh-hannover.de>
 * @version     SVN: $Id$
 */
class Tx_MvcExtjs_ExtJS_DirectApi {

	/**
	 * @var Tx_Extbase_Reflection_Service
	 */
	protected $reflectionService;
	
	/**
	 * @var array
	 */
	protected $frameworkConfiguration;
	
	/**
	 * @var string
	 */
	protected $cacheStorageKey;
	
	public function __construct() {
		$this->reflectionService = t3lib_div::makeInstance('Tx_Extbase_Reflection_Service');
		$this->frameworkConfiguration = Tx_MvcExtjs_Dispatcher::getExtbaseFrameworkConfiguration();
		$this->cacheStorageKey = 'Tx_MvcExtjs_ExtDirect_API_' . $this->frameworkConfiguration['pluginName'];
	}
	
	/**
	 * Fetches the API from cache_hash or ceates an API
	 * 
	 * @param string $routeUrl
	 * @param string $namespace
	 * @param boolean $readFromCache Should the cache be used when reading the data.
	 * @param boolean $writeToCache Should the created api be stored in the cache.  
	 * @return array
	 */
	public function getApi($routeUrl = '', $namespace = 'Ext.ux.TYPO3.app', $readFromCache = TRUE, $writeToCache = TRUE) {
		$cacheHash = md5($this->cacheStorageKey . serialize($this->frameworkConfiguration['switchableControllerActions']));
		$cachedApi = ($readFromCache) ? t3lib_pageSelect::getHash($cacheHash) : FALSE;
		
		if ($cachedApi) {
			$api = unserialize(t3lib_pageSelect::getHash($cacheHash));
		} else {
			$api = $this->createApi($routeUrl,$namespace);
			if ($writeToCache) {
				t3lib_pageSelect::storeHash($cacheHash,serialize($api),$this->cacheStorageKey);
			}
		}
		return $api;
	}
	
	/**
	 * Creates the remote api based on the module/plugin configuration using the extbase
	 * reflection features.
	 * 
	 * @param string $routeUrl
	 * @param string $namespace
	 * @return array
	 */
	protected function createApi($routeUrl,$namespace) {
		$reflectionService = t3lib_div::makeInstance('Tx_Extbase_Reflection_Service');
		
		$api = array();
		$api['url'] = $routeUrl;
		$api['type'] = 'remoting';
		$api['namespace'] = $namespace;
		$api['actions'] = array();
		
		foreach ($this->frameworkConfiguration['switchableControllerActions'] as $allowedControllerActions) {
			$controllerName = $allowedControllerActions['controller'];
			$unstrippedControllerName = $controllerName . 'Controller';
			$controllerObjectName = 'Tx_' . $this->frameworkConfiguration['extensionName'] . '_Controller_' . $unstrippedControllerName;
			$actions = t3lib_div::trimExplode(',',$allowedControllerActions['actions']);
			$controllerActions = array();
			foreach ($actions as $actionName) {
				$unstrippedActionName = $actionName . 'Action';
				try  {
					$actionParameters = $reflectionService->getMethodParameters($controllerObjectName,$unstrippedActionName);
					$controllerActions[] = array(
						'len' => count($actionParameters),
						'name' => $unstrippedActionName
					);
				} catch (ReflectionException $re) {
					if ($unstrippedActionName !== 'extObjAction') {
						t3lib_div::sysLog('You have a not existing action (' . $controllerObjectName . '::' . $unstrippedActionName . ') in your module/plugin configuration. This action will not be available for Ext.Direct remote execution.','MvcExtjs',1);
					}
				}
			}
			$api['actions'][$unstrippedControllerName] = $controllerActions;
		}
		return $api;
	}

}
?>