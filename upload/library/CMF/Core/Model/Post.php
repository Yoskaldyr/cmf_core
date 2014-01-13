<?php

/**
 * Post model
 *
 * @package CMF_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class CMF_Core_Model_Post extends XFCP_CMF_Core_Model_Post
{
	public function preparePostJoinOptions(array $fetchOptions)
	{
		$postFetchOptions = parent::preparePostJoinOptions($fetchOptions);

		return CMF_Core_Application::prepareFetchOptions($postFetchOptions, 'XenForo_DataWriter_DiscussionMessage_Post');
	}

	public function preparePost(array $post, array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
	{
		return CMF_Core_Application::unserializeDataByKey($post, 'XenForo_DataWriter_DiscussionMessage_Post');
	}
}