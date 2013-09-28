# Kohana Log ICQ

Module for sending log messages in site admin ICQ.

To enable, open your `application/bootstrap.php` file and modify the call to [Kohana::modules] by including the gravatar module like so:
~~~
Kohana::modules(array(
	...
	'log_icq' => MODPATH.'log_icq', // ICQ logging
	...
));
~~~
and
~~~
Kohana::$log->attach(new Log_ICQ);
~~~