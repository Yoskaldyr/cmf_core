<?php

/**
 * Node model
 *
 * @package CMF_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class CMF_Core_Model_Node extends XFCP_CMF_Core_Model_Node
{

	public function unserializeNodeFields($node, $force = false)
	{
		if (!$this->_isNode($node) || (!empty($node['__nodeUnserialized']) && !$force))
		{
			return $node;
		}

		$nodeClasses = array('XenForo_DataWriter_Node');

		if (isset($node['node_type_id']) && ($nodeType = $this->getNodeTypeById($node['node_type_id'])))
		{
			$nodeClasses[] = $nodeType['datawriter_class'];
		}
		$node = CMF_Core_Application::unserializeDataByKey($node, $nodeClasses);
		$node['__nodeUnserialized'] = true;

		return $node;
	}

	public function isUnserializedNodeFields($node)
	{
		return ($this->_isNode($node) && !empty($node['__nodeUnserialized']));
	}

	public function getNodeById($nodeId, array $fetchOptions = array())
	{
		return $this->unserializeNodeFields(parent::getNodeById($nodeId, $fetchOptions));
	}

	public function getNodeByName($nodeName, $nodeTypeId, array $fetchOptions = array())
	{
		return $this->unserializeNodeFields(parent::getNodeByName($nodeName, $nodeTypeId, $fetchOptions));
	}

	public function prepareNodeForAdmin(array $node)
	{
		return $this->unserializeNodeFields(parent::prepareNodeForAdmin($node));
	}

	public function prepareNodesWithHandlers(array $nodes, array $nodeHandlers)
	{
		$nodes = parent::prepareNodesWithHandlers($nodes, $nodeHandlers);
		foreach ($nodes AS &$node)
		{
			$node = $this->unserializeNodeFields($node);
		}
		return $nodes;
	}
}