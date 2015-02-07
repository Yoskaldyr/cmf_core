<?php
/**
 * User: Yoskaldyr
 * Date: 26.12.2014
 * Time: 0:28
 */ 
class CMF_Core_ViewPublic_Attachment_View extends XFCP_CMF_Core_ViewPublic_Attachment_View
{
	public function renderRaw()
	{
		/** @var XenForo_FileOutput $content */
		$content = parent::renderRaw();
		if ($content instanceof XenForo_FileOutput)
		{
			$filename = $content->getFileName();
			switch (XenForo_Application::get('options')->cmfDownloadMode)
			{
				case 'xaccel':
					/** @var XenForo_Application $app */
					$app = XenForo_Application::getInstance();
					$root = $app->getRootDir();
					$this->_response->setHeader('X-Accel-Redirect', (strpos($filename, $root) === 0) ? substr($filename, strlen($root)) : $filename);
					return '';
				case 'xsendfile':
					$this->_response->setHeader('X-Sendfile', $filename);
					return '';
				default:
					return $content;

			}
		}
		else
		{
			return $content;
		}
	}
}