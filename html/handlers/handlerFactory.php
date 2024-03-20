<?php
/**
 * Handler factory
 */
class HandlerFactory
{
	public static function create($handler, $session = null, $db = null, $purifier = null, $api = null)
	{
		switch ($handler) {
			case 'logout':
				return new LogoutHandler($session, $db);
			case 'api':
				return new ApiHandler($purifier, $api);
			default:
				throw new Exception("Invalid handler: $handler");
		}
	}
}
