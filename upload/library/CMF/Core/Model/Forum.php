<?php
/**
 * Forum model
 *
 * @package CMF_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 *
 * @method CMF_Core_Model_Node getModelFromCache
 */
class CMF_Core_Model_Forum extends XFCP_CMF_Core_Model_Forum
{
	/**
	 * Checks the 'join' key of the incoming array for the presence of the FETCH_x bitfields in this class
	 * and returns SQL snippets to join the specified tables if required
	 *
	 * @param array $fetchOptions Array containing a 'join' integer key build from this class's FETCH_x bitfields and other keys
	 *
	 * @return array Containing 'selectFields' and 'joinTables' keys. Example: selectFields = ', user.*, foo.title'; joinTables = ' INNER JOIN foo ON (foo.id = other.id) '
	 */
	public function prepareForumJoinOptions(array $fetchOptions)
	{
		$forumFetchOptions = parent::prepareForumJoinOptions($fetchOptions);
		$dwCoreFields = CMF_Core_Application::getInstance()->get(
			CMF_Core_Application::DW_FIELDS,
			array('XenForo_DataWriter_Node', 'XenForo_DataWriter_Forum')
		);

		unset($dwCoreFields['xf_forum'], $dwCoreFields['xf_node']);
		if ($dwCoreFields && is_array($dwCoreFields))
		{
			foreach ($dwCoreFields as $table => $fields)
			{
				unset($fields['node_id']);
				if ($fields && is_array($fields))
				{
					$forumFetchOptions['selectFields'] .= ',
					' . $table . '.' . implode(', ' . $table . '.', $fields);

					$forumFetchOptions['joinTables'] .= '
					LEFT JOIN ' . $table . ' AS ' . $table . ' ON
							(node.node_id = ' . $table . '.node_id)';
				}
			}
		}

		return $forumFetchOptions;
	}

	public function prepareForum(array $forum)
	{
		return $this->getModelFromCache('XenForo_Model_Node')->unserializeNodeFields(parent::prepareForum($forum));
	}
}