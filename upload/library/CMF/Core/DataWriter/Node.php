<?php
/**
 * Data writer for all node types.
 * Dynamic proxy class
 *
 * @package CMF_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 *
 * @method array getUnserialize($key, $onlyArray = false)
 * @method boolean _validateSerializedArray(&$data)
 * @method CMF_Core_Model_Node _getNodeModel()
 */
class CMF_Core_DataWriter_Node extends XFCP_CMF_Core_DataWriter_Node
{
	protected $_currentNodeType = false;

	/**
	 * Post Save for handling reset child nodes data
	 */
	protected function _postSave()
	{
		parent::_postSave();

		if ($nodeType = $this->getNodeType())
		{
			$typeName = $nodeType['datawriter_class'];
			if ($this->getOption(CMF_Core_Application::DW_ENABLE_OPTION)
				&& ($extra = $this->getExtraData(CMF_Core_Application::DW_EXTRA))
				&& !empty($extra['cmf_reset'])
				&& is_array($extra['cmf_reset'])
				&& ($dwData = CMF_Core_Application::getMerged(CMF_Core_Application::DW_DATA, $typeName, true))
			)
			{
				$resetFields = $extra['cmf_reset'];
				unset($extra['cmf_reset']);
				$resetData = array();
				$nodeTypes = $this->_getNodeModel()->getAllNodeTypes();

				foreach ($resetFields as $name => $field)
				{
					if ($field && isset($dwData[$name]))
					{
						//applying to all node types or only selected
						$applyTypes = (($field == 1) || ($field == 'all')) ? array_keys($nodeTypes) : explode(',', $field);
						foreach ($applyTypes as $nodeTypeId)
						{
							if (isset($nodeTypes[$nodeTypeId]))
							{
								$resetData[$nodeTypeId][$name] = $dwData[$name];
							}
						}
					}
				}
				if ($resetData && ($childNodes = $this->_getNodeModel()->getChildNodes($this->getMergedData())))
				{
					foreach ($childNodes as $node)
					{
						$nodeTypeId = $node['node_type_id'];
						if (
							isset(
								$nodeTypes[$nodeTypeId]['datawriter_class'],
								$resetData[$nodeTypeId]
							)
						)
						{
							/* @var $writer XenForo_DataWriter_Node */
							$writer = XenForo_DataWriter::create($nodeTypes[$nodeTypeId]['datawriter_class']);

							// prevent any child updates from occuring - we're handling it here
							$writer->setOption(XenForo_DataWriter_Node::OPTION_POST_WRITE_UPDATE_CHILD_NODES, false);
							// prevent any child use input data
							$writer->setOption(CMF_Core_Application::DW_ENABLE_OPTION, false);
							//if ($extra)
							//{
								//setting additional settings (can be changed later)
								//$writer->setExtraData(CMF_Core_Application::DW_EXTRA, $extra);
							//}
							// we already have the data, don't go and query it again
							$writer->setExistingData($node, true);
							$writer->bulkSet($resetData[$nodeTypeId]);
							$writer->save();
						}
					}
				}
			}
		}
	}

	/**
	 * Gets the current node type
	 * @return array|boolean
	 */
	public function getNodeType()
	{
		if ($this->_currentNodeType)
		{
			return $this->_currentNodeType;
		}
		if ($nodeTypes = $this->_getNodeModel()->getAllNodeTypes())
		{
			foreach ($nodeTypes as $nodeType)
			{
				if ($this instanceof $nodeType['datawriter_class'])
				{
					return $this->_currentNodeType = $nodeType;
				}
			}
		}
		//base node type
		return false;
	}
}