<?php

/**
 * Controller for handling actions on threads.
 *
 * @package CMF_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class CMF_Core_ControllerPublic_Thread extends XFCP_CMF_Core_ControllerPublic_Thread
{

	public function actionSave()
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

		return parent::actionSave();
	}
}