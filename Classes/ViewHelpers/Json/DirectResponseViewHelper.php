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
 * A ViewHelper which renders a Ext.Direct Response.
 * 
 * @category    ViewHelpers
 * @package     TYPO3
 * @subpackage  tx_mvcextjs
 * @author      Dennis Ahrens <dennis.ahrens@fh-hannover.de>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id:
 */
class Tx_MvcExtjs_ViewHelpers_Json_DirectResponseViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper implements Tx_Fluid_Core_ViewHelper_Facets_ChildNodeAccessInterface {
	
	/**
	 * An array of Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode
	 * @var array
	 */
	protected $childNodes = array();
	
	/**
	 * @var Tx_Fluid_Core_Rendering_RenderingContext
	 */
	protected $renderingContext;
	
	/**
	 * Setter for ChildNodes - as defined in ChildNodeAccessInterface
	 *
	 * @param array $childNodes Child nodes of this syntax tree node
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function setChildNodes(array $childNodes) {
		$this->childNodes = $childNodes;
	}
	
	/**
	 * Sets the rendering context which needs to be passed on to child nodes
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContext $renderingContext the renderingcontext to use
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setRenderingContext(Tx_Fluid_Core_Rendering_RenderingContext $renderingContext) {
		$this->renderingContext = $renderingContext;
	}
	
	/**
	 * Renders a response for an Ext.Direct request.
	 * The result property is filled up with all ViewHelpers that were
	 * children of this ViewHelper.
	 * The child ViewHelpers *MUST* answer in correct json format.
	 * If there is more than one given the result is merged.
	 * 
	 * @param string $type The type of the answer.
	 * @param int $tid The Ext.Direct transaction Id.
	 * @param string $action The Controller that answers. (ExtJS calls the controller: action)
	 * @param string $method The Action that answers. (ExtJS calls the action: method)
	 * @return string
	 * @author Dennis Ahrens <dennis.ahrens@fh-hannover.de>
	 */
	public function render($type = 'rpc',
						   $tid = NULL,
						   $action = NULL,
						   $method = NULL) {
						   	
		if ($tid === NULL) {
			$tid = $this->controllerContext->getRequest()->getArgument('tid');
		}
		if ($action === NULL) {
			$action = $this->controllerContext->getRequest()->getControllerName() . 'Controller';
		}
		if ($method === NULL) {
			$method = $this->controllerContext->getRequest()->getControllerActionName() . 'Action';
		}
		
		$result = array();
		foreach($this->childNodes as $childNode) {
			if ($childNode instanceof Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode) {
				$childNode->setRenderingContext($this->renderingContext);
				$viewHelperResult = json_decode($childNode->evaluate(),true);
				$result = array_merge($result,$viewHelperResult);
			}
		}
		
		$responseArray = array();
		$responseArray['type'] = $type;
		$responseArray['tid'] = $tid;
		$responseArray['action'] = $action;
		$responseArray['method'] = $method;
		$responseArray['result'] = $result;
		
		return json_encode($responseArray);
	}

}
?>