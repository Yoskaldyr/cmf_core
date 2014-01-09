<?php

/**
 * Controller for handling actions on posts.
 *
 * @package CMF_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class CMF_Core_ControllerPublic_Post extends XFCP_CMF_Core_ControllerPublic_Post
{

	public function actionSave()
	{
		$core = CMF_Core_Application::getInstance();
		$core->set(
			CMF_Core_Application::DW_DATA,
			'XenForo_DataWriter_DiscussionMessage_Post',
			$this->_input->filter(
				$core->get(
					CMF_Core_Application::INPUT_FIELDS,
					'XenForo_DataWriter_DiscussionMessage_Post'
				)
			)
		);

		return parent::actionSave();
	}
}