<?php

/**
 * Data writer for threads.
 *
 * @package CMF_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class CMF_Core_DataWriter_Thread extends XFCP_CMF_Core_DataWriter_Thread
{

	protected function _getFields()
	{
		$dwFields = parent::_getFields();
		if ($dwCoreFields = CMF_Core_Application::getInstance()->get(
			CMF_Core_Application::DW_FIELDS,
			'XenForo_DataWriter_Discussion_Thread'
		))
		{
			$dwFields = XenForo_Application::mapMerge($dwFields, $dwCoreFields);
		}

		return $dwFields;
	}
}