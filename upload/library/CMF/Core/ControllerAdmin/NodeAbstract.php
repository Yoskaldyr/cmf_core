<?php
/**
 * CMF Base DataWriter class
 *
 * @package CMF_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 *
 * @method CMF_Core_Model_Node _getNodeModel()
 */
abstract class CMF_Core_ControllerAdmin_NodeAbstract extends XFCP_CMF_Core_ControllerAdmin_NodeAbstract
{
	/**
	 * One of the pre-dispatch behaviors for the whole set of admin controllers.
	 * but only for node sub classes
	 */
	protected function _assertInstallLocked($action)
	{
		parent::_assertInstallLocked($action);

		if (($nodeType = $this->_getNodeType()) && $action == 'Save')
		{
			$inputFields = CMF_Core_Application::getMerged(
				CMF_Core_Application::INPUT_FIELDS,
				array($nodeType['datawriter_class']),
				//todo remove 'remove flag'
				false, true
			);
			$inputFields['cmf_reset'] = XenForo_Input::ARRAY_SIMPLE;

			CMF_Core_Application::setMerged(
				CMF_Core_Application::DW_DATA,
				$nodeType['datawriter_class'],
				$this->_input->filter($inputFields)
			);
		}
	}

	/**
	 * One of the post-dispatch behaviors for the whole set of admin controllers.
	 * but only for node sub classes
	 */
	protected function _logAdminRequest($controllerResponse, $controllerName, $action)
	{
		parent::_logAdminRequest($controllerResponse, $controllerName, $action);

		if (($nodeType = $this->_getNodeType()) && $action == 'Edit')
		{
			$paramName = strtolower($nodeType['node_type_id']);
			if ($paramName == 'linkforum')
			{
				$paramName = 'link';
			}
			if ($controllerResponse instanceof XenForo_ControllerResponse_View && !empty($controllerResponse->params[$paramName]))
			{
				$controllerResponse->params[$paramName] = $this->_getNodeModel()->unserializeNodeFields($controllerResponse->params[$paramName]);
			}
		}
	}

	protected function _getNodeType()
	{
		if ($nodeTypes = $this->_getNodeModel()->getAllNodeTypes())
		{
			foreach ($nodeTypes as $nodeType)
			{
				if ($this instanceof $nodeType['controller_admin_class'])
				{
					return $nodeType;
				}
			}
		}
		return false;
	}
}