<?php
	require_once(__DIR__ . '/../vendor/autoload.php');
	require_once(__DIR__ . '/../functions.php');

	// Router for requests
	$router = new \Bramus\Router\Router();

	// Templating engine
	$displayEngine = new DisplayEngine($config['templates']);
	$displayEngine->setSiteName($config['sitename']);

	// API to interact with backend
	$api = new MyDNSHostAPI($config['api']);
	if (session::exists('logindata')) {
		$api->setAuth(session::get('logindata'));

		if (session::exists('impersonate')) {
			$api->impersonate(session::get('impersonate'), 'id');

			$displayEngine->setVar('impersonating', true);
		}
	}

	// Routes that exist all the time.
	(new SiteRoutes())->addRoutes($router, $displayEngine, $api);


	// If we have valid auth details then present a useful session, otherwise
	// present a login-only session.
	$userdata = $api->getUserData();
	if ($userdata !== NULL) {
		session::setCurrentUser($userdata);

		$isAdmin = (isset($userdata['user']['admin']) && $userdata['user']['admin'] == 'true');
		session::set('isadmin', $isAdmin);
		session::set('domains', $api->getDomains());

		(new AuthedRoutes())->addRoutes($router, $displayEngine, $api);
		(new DomainRoutes())->addRoutes($router, $displayEngine, $api);
		(new UserRoutes())->addRoutes($router, $displayEngine, $api);

		if ($isAdmin) {
			(new AdminRoutes())->addRoutes($router, $displayEngine, $api);
		}
	} else {
		$hadLoginDetails = session::exists('logindata');
		session::clear(['DisplayEngine::Flash']);

		if ($hadLoginDetails) {
			$displayEngine->flash('info', 'Session timeout', 'Your login session has timed out. Please log in again.');

			header('Location: ' . $displayEngine->getURL('/login'));
			die();
		}

		(new NotAuthedRoutes())->addRoutes($router, $displayEngine, $api);
	}

	// Begin!
	$router->run();
