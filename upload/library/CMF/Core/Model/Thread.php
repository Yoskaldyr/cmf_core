<?php

/**
 * Thread model
 *
 * @package CMF_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 */
class CMF_Core_Model_Thread extends XFCP_CMF_Core_Model_Thread
{

	public function prepareThreadFetchOptions(array $fetchOptions)
	{
		$threadFetchOptions = parent::prepareThreadFetchOptions($fetchOptions);

		return CMF_Core_Application::prepareFetchOptions($threadFetchOptions, 'XenForo_DataWriter_Discussion_Thread');
	}

	public function prepareThread(array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
	{
		/** @var CMF_Core_Model_Node $nodeModel */
		$nodeModel = $this->getModelFromCache('XenForo_Model_Node');
		if (!$nodeModel->isUnserializedNodeFields($forum))
		{
			$forum = $nodeModel->unserializeNodeFields($forum);
		}
		return CMF_Core_Application::unserializeDataByKey(parent::prepareThread($thread, $forum, $nodePermissions, $viewingUser), 'XenForo_DataWriter_Discussion_Thread');
	}
}