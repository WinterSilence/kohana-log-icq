<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Send log messages in ICQ
 *
 * @package    Kohana
 * @category   Logging
 * @author     WinterSilence
 * @copyright  (c) 2013 handy-soft.ru
 * @license    MIT
 */
abstract class Kohana_Log_ICQ extends Log_Writer {

	/**
	 * @var  array
	 */
	protected $_config;

	/**
	 * @var  ICQ
	 */
	protected $_icq;

	/**
	 * Load configuration and connect ICQ
	 *
	 *     $writer = new Log_Icq($config);
	 *
	 * @param   array  $config
	 * @return  void
	 */
	public function __construct(array $config = array())
	{
		$this->_config = array_merge(Kohana::$config->load('log_icq')->as_array(), $config);
		
		if (class_exists('Icq', FALSE))
		{
			include_once Kohana::find_file('vendor', 'Icq'.DIRECTORY_SEPARATOR.'Icq');
		}
		
		$this->_icq = new Icq($this->_config['from_uin'], $this->_config['from_password'])
		if ( ! $this->_icq->is_connected())
		{
			// throw new Kohana_Exception($this->_icq->error);
			Kohana::$log->add(Log::NOTICE, 'Log_Icq: '.$this->_icq->error);
		}
	}

	/**
	 * Send messages with critical level in ICQ
	 *
	 *     $writer->write($messages);
	 *
	 * @param   array   $messages
	 * @return  void
	 */
	public function write(array $messages)
	{
		if ( ! $this->_icq->is_connected())
		{
			return NULL;
		}
		
		foreach ($messages as $message)
		{
			if ($this->_config['allowable_level'] > $message['level'])
			{
				// iconv('UTF-8', 'CP1251', $this->format_message($message))
				if ( ! $this->_icq->write_message($this->_config['to_uin'], $this->format_message($message)))
				{
					// throw new Kohana_Exception($this->_icq->error);
					Kohana::$log->add(Log::NOTICE, 'Log_Icq: '.$this->_icq->error);
				}
			}
		}
		
		$this->_icq->close();
	}

}