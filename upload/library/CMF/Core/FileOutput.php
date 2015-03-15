<?php
/**
 * User: Yoskaldyr
 * Date: 15.03.2015
 * Time: 15:21
 */ 
class CMF_Core_FileOutput extends XFCP_CMF_Core_FileOutput
{

	public function output()
	{
		if ($this->_contents === null && !headers_sent())
		{
			switch (XenForo_Application::get('options')->cmfDownloadMode)
			{
				case 'xaccel':
					/** @var XenForo_Application $app */
					$app = XenForo_Application::getInstance();
					$root = $app->getRootDir();
					header('X-Accel-Redirect: ' . ((strpos($this->_fileName, $root) === 0) ? substr($this->_fileName, strlen($root)) : $this->_fileName));
					break;
				case 'xsendfile':
					header('X-Sendfile: ' . $this->_fileName);
					break;
				default:
					parent::output();
			}
		}
		else
		{
			parent::output();
		}
	}
}