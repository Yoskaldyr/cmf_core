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
		$content = parent::renderRaw();
		if ($content instanceof XenForo_FileOutput)
		{
			switch (XenForo_Application::get('options')->cmfDownloadMode)
			{
				case 'xaccel':
					/** @var XenForo_Application $app */
					$app = XenForo_Application::getInstance();
					$root = $app->getRootDir();
					$this->_response->setHeader('X-Accel-Redirect', (strpos($this->_params['attachmentFile'], $root) === 0) ? substr($this->_params['attachmentFile'], strlen($root)) : $this->_params['attachmentFile']);
					return '';
				case 'xsendfile':
					$this->_response->setHeader('X-Sendfile', $this->_params['attachmentFile']);
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