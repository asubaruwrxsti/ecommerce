<?php
/**
 * Handler factory
 */
class HandlerFactory
{
	public static function create($handler, $session = null, $db = null, $purifier = null, $api = null, $view = null)
	{
		switch ($handler) {
			case 'logout':
				return new LogoutHandler($session, $db);
			case 'api':
				return new ApiHandler($purifier, $api);
			default:
				$method = 'render' . $handler;
				if(method_exists($view, $method)) {
					$view->$method($handler, $session, $db);
				} else {
					$view->render('404');
				}
				break;
		}
	}
}
