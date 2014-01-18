<?php

class CMF_Core_Install
{
	public static function install($existingAddOn, $addOnData)
	{
		if (XenForo_Application::$versionId < 1020470)
		{
		    throw new XenForo_Exception('This Add-On requires XenForo version 1.2.4 or higher.');
		}
	}
}