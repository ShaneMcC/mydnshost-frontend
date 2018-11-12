<?php
	class NotAuthedRoutes {

		public function addRoutes($router, $displayEngine, $api) {

			$router->get('/login', function() use ($displayEngine) {
				$displayEngine->setTitle('login');
				$displayEngine->setPageID('login');
				$displayEngine->display('login.tpl');
			});

			$router->get('/2fa', function() use ($displayEngine) {
				$displayEngine->setTitle('2fa');
				$displayEngine->setPageID('2fa');

				if (isset($_COOKIE['MYDNSHOST_2FA_SAVED_DEVICE'])) {
					$deviceData = json_decode($_COOKIE['MYDNSHOST_2FA_SAVED_DEVICE'], true);
					if (isset($deviceData['name'])) {
						$displayEngine->setVar('devicename', $deviceData['name']);
					}
				}

				$displayEngine->display('2fa.tpl');
			});

			$router->post('/login', function() use ($displayEngine, $api) {
				$lastAttempt = session::exists('lastlogin') ? session::get('lastlogin') : [];
				session::remove('lastlogin');

				if (isset($_POST['2fakey']) && isset($lastAttempt['user']) && isset($lastAttempt['pass'])) {
					$user = $lastAttempt['user'];
					$pass = $lastAttempt['pass'];

					$key = $_POST['2fakey'];

					if (isset($_POST['savedevice'])) {
						if (isset($_POST['devicename']) && !empty($_POST['devicename'])) {
							$api->setDeviceName($_POST['devicename']);
						} else {
							$api->setDeviceName('.');
						}
					}
				} else if (isset($_POST['user']) && isset($_POST['pass'])) {
					$user = $_POST['user'];
					$pass = $_POST['pass'];
					$key = '';
				} else {
					$displayEngine->flash('error', 'Login Error', 'There was an error with the details provided.');
					session::clear(['DisplayEngine::Flash', 'wantedPage']);
					header('Location: ' . $displayEngine->getURL('/login'));
					return;
				}

				$api->setAuthUserPass($user, $pass, $key);
				$sessionID = $api->getSessionID();

				if ($sessionID !== NULL) {
					$lr = $api->getLastResponse();

					if (isset($lr['device_id']) || isset($lr['device_name'])) {
						$deviceData = [];

						if (isset($lr['device_id'])) { $deviceData['id'] = $lr['device_id']; }
						if (isset($lr['device_name'])) { $deviceData['name'] = $lr['device_name']; }

						setcookie('MYDNSHOST_2FA_SAVED_DEVICE', json_encode($deviceData), (time() + (60 * 60 * 24 * 31)));
					}

					$displayEngine->flash('success', 'Success!', 'You are now logged in.');

					session::set('logindata', ['type' => 'session', 'sessionid' => $sessionID]);
					session::set('csrftoken', genUUID());

					if (session::exists('wantedPage')) {
						header('Location: ' . $displayEngine->getURL(session::get('wantedPage')));
						session::remove('wantedPage');
					} else {
						header('Location: ' . $displayEngine->getURL('/'));
					}
					return;
				} else {
					$lr = $api->getLastResponse();

					session::setCurrentUser(null);
					if (isset($lr['login_error']) && $lr['login_error'] == '2fa_required' && isset($_POST['user']) && isset($_POST['pass'])) {
						session::set('lastlogin', $_POST);
						header('Location: ' . $displayEngine->getURL('/2fa'));
					} else {
						if (isset($lr['errorData'])) {
							$displayEngine->flash('error', 'Login Error', 'There was an error with the details provided: ' . $lr['errorData'][0]);
						} else {
							$displayEngine->flash('error', 'Login Error', 'There was an error with the details provided.');
						}

						session::clear(['DisplayEngine::Flash', 'wantedPage']);
						header('Location: ' . $displayEngine->getURL('/login'));
					}

					return;
				}
			});
		}
	}
