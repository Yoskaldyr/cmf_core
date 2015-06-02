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
					/** @noinspection PhpUndefinedMethodInspection */
					$root = XenForo_Application::getInstance()->getRootDir() . '/';
					$path = rtrim((strpos($this->_fileName, $root) === 0) ? substr($this->_fileName, strlen($root)) : $this->_fileName, '/');
					$base = parse_url(rtrim(XenForo_Application::get('options')->boardUrl, '/ ') . '/', PHP_URL_PATH);
					header('X-Accel-Redirect: ' . $base . $path);
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