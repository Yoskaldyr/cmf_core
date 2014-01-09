<?php

/**
 * Controller for handling actions on forums.
 *
 * @package CMF_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class CMF_Core_ControllerPublic_Forum extends XFCP_CMF_Core_ControllerPublic_Forum
{

	public function actionAddThread()
	{
		$core = CMF_Core_Application::getInstance();
		$core->set(
			CMF_Core_Application::DW_DATA,
			'XenForo_DataWriter_Discussion_Thread',
			$this->_input->filter(
				$core->get(
					CMF_Core_Application::INPUT_FIELDS,
					'XenForo_DataWriter_Discussion_Thread'
				)
			)
		);

		return parent::actionAddThread();
	}
}