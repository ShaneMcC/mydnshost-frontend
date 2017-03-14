<?php
	class AdminDomainRoutes extends DomainRoutes {
		public function setAccessVars($displayEngine, $domainData) {
			parent::setAccessVars($displayEngine, $domainData);

			$displayEngine->setVar('has_domain_owner', true);
			$displayEngine->setVar('has_domain_admin', true);
			$displayEngine->setVar('has_domain_write', true);
			$displayEngine->setVar('has_domain_read', true);

			$displayEngine->setVar('domain_access_level', 'Owner (override)');
		}

		public function getURL($displayEngine, $location) {
			return $displayEngine->getURL('/admin/' . ltrim($location, '/'));
		}

		public function setPageID($displayEngine, $pageid) {
			return $displayEngine->setPageID('/admin/domains');
		}

		public function setVars($displayEngine) {
			$displayEngine->setVar('pathprepend', '/admin');

			$displayEngine->getTwig()->addFunction(new Twig_Function('canChangeAccess', function($email) { return true; }));
		}
	}

	class DomainRoutes {

		public function setAccessVars($displayEngine, $domainData) {
			if (!isset($domainData['access'])) { $domainData['access'] = 'none'; }
			$displayEngine->setVar('has_domain_owner', in_array($domainData['access'], ['owner']));
			$displayEngine->setVar('has_domain_admin', in_array($domainData['access'], ['owner', 'admin']));
			$displayEngine->setVar('has_domain_write', in_array($domainData['access'], ['owner', 'admin', 'write']));
			$displayEngine->setVar('has_domain_read', in_array($domainData['access'], ['owner', 'admin', 'write', 'read']));

			$displayEngine->setVar('domain_access_level', $domainData['access']);
		}

		public function getURL($displayEngine, $location) {
			return $displayEngine->getURL($location);
		}

		public function setPageID($displayEngine, $pageid) {
			return $displayEngine->setPageID($pageid);
		}

		public function setVars($displayEngine) {
			$displayEngine->setVar('pathprepend', '');

			$displayEngine->getTwig()->addFunction(new Twig_Function('canChangeAccess', function($email) { return $email != session::getCurrentUser()['user']['email']; }));
		}

		public function addRoutes($router, $displayEngine, $api) {

			$router->get('/domains', function() use ($router, $displayEngine, $api) {
				$this->setVars($displayEngine);

				$domains = $api->getDomains();
				$allDomains = [];
				foreach ($domains as $domain => $access) {
					$allDomains[] = ['domain' => $domain, 'access' => $access];
				}
				$displayEngine->setVar('domains', $allDomains);
				$displayEngine->display('alldomains.tpl');
			});

			$router->match('GET|POST', '/domain/([^/]+)', function($domain) use ($router, $displayEngine, $api) {
				$this->setVars($displayEngine);

				$domainData = $api->getDomainData($domain);
				$this->setPageID($displayEngine, '/domain/' . $domain)->setTitle('Domain :: ' . $domain);

				if ($domainData !== NULL) {
					// Change SOA Stuff.
					if ($router->getRequestMethod() == "POST" && isset($_POST['changetype']) && $_POST['changetype'] == 'soa') {

						$data = ['disabled' => false, 'SOA' => []];
						if (isset($_POST['disabled'])) {
							$data['disabled'] = $_POST['disabled'];
						}
						if (isset($_POST['soa'])) {
							$data['SOA'] = $_POST['soa'];
						}

						$result = $api->setDomainData($domain, $data);

						if (array_key_exists('error', $result)) {
							$displayEngine->flash('error', '', 'There was an error with the soa data provided.');
						} else {
							$displayEngine->flash('success', '', 'Your changes have been saved.');

							header('Location: ' . $this->getURL($displayEngine, '/domain/' . $domain));
							return;
						}
					} else if ($router->getRequestMethod() == "POST" && isset($_POST['changetype']) && $_POST['changetype'] == 'access') {
						$edited = isset($_POST['access']) ? $_POST['access'] : [];
						$new = isset($_POST['newAccess']) ? $_POST['newAccess'] : [];

						// Try to submit, to see if we have any errors.
						$data = ['access' => []];
						foreach ($edited as $id => $access) {
							$data['access'][$id] = $access['level'];
						}
						foreach ($new as $access) {
							$data['access'][$access['who']] = $access['level'];
						}

						$result = $api->setDomainAccess($domain, $data);

						if (array_key_exists('error', $result)) {
							$displayEngine->flash('error', '', ['There was an error: '. $result['error'], 'None of the changes have been saved. Please fix the problems and then try again.']);
						} else {
							$displayEngine->flash('success', '', 'Your changes have been saved.');

							header('Location: ' . $this->getURL($displayEngine, '/domain/' . $domain));
							return;
						}

						$displayEngine->setVar('newaccess', $new);
						$displayEngine->setVar('editedaccess', $edited);
					}

					$domains = session::get('domains');
					if (isset($domains[$domain])) {
						$domainData['access'] = $domains[$domain];
					}
					$displayEngine->setVar('domain', $domainData);
					$this->setAccessVars($displayEngine, $domainData);

					$displayEngine->setVar('domainaccess', $api->getDomainAccess($domain));

					$displayEngine->display('domain.tpl');
				} else {
					$displayEngine->setVar('unknowndomain', $domain);
					$displayEngine->display('unknown_domain.tpl');
				}
			});

			$router->match('GET|POST', '/domain/([^/]+)/records', function($domain) use ($router, $displayEngine, $api) {
				$this->setVars($displayEngine);

				$domainData = $api->getDomainData($domain);
				$this->setPageID($displayEngine, '/domain/' . $domain)->setTitle('Domain :: ' . $domain . ' :: Records');

				if ($domainData !== NULL) {
					$records = [];

					// Handle POST first.
					if ($router->getRequestMethod() == "POST") {
						$submitted['edited'] = isset($_POST['record']) ? $_POST['record'] : [];
						$submitted['new'] = isset($_POST['newRecord']) ? $_POST['newRecord'] : [];

						$errorMap = [];

						// Try to submit, to see if we have any errors.
						$data = ['records' => []];
						foreach (['edited', 'new'] as $rtype) {
							foreach ($submitted[$rtype] as $id => $record) {
								if ($rtype == 'edited') {
									$record['id'] = $id;
								}

								$data['records'][] = $record;
								$errorMap[] = [$rtype, $id];
							}
						}

						$result = $api->setDomainRecords($domain, $data);

						if (array_key_exists('errorData', $result)) {
							foreach ($result['errorData'] as $id => $error) {
								if (array_key_exists($id, $errorMap)) {
									list($rtype, $rid) = $errorMap[$id];

									if (startsWith($error, 'Unable to validate record:')) {
										$error = explode(':', $error, 2);
										$error = trim(isset($error[1]) ? $error[1] : $error[0]);
									}

									$submitted[$rtype][$rid]['errorData'] = $error;
								}
							}
							$displayEngine->flash('error', '', 'There was some errors with some of the submitted records. None of the changes have been saved. Please fix the problems and then try again.');
						} else {
							$displayEngine->flash('success', '', 'Your changes have been saved.');

							header('Location: ' . $this->getURL($displayEngine, '/domain/' . $domain . '/records'));
							return;
						}

						$records = $api->getDomainRecords($domain);
						foreach ($records as &$record) {
							if (array_key_exists($record['id'], $submitted['edited'])) {
								if (array_key_exists('delete', $submitted['edited'][$record['id']])) {
									$record['deleted'] = true;
								} else {
									$record['edited'] = $submitted['edited'][$record['id']];

									if (array_key_exists('errorData', $submitted['edited'][$record['id']])) {
										$record['errorData'] = $submitted['edited'][$record['id']]['errorData'];
									}
								}
							}
						}

						$displayEngine->setVar('newRecords', $submitted['new']);
					} else {
						$records = $api->getDomainRecords($domain);
					}

					$domains = session::get('domains');
					if (isset($domains[$domain])) {
						$domainData['access'] = $domains[$domain];
					}
					$displayEngine->setVar('domain', $domainData);
					$this->setAccessVars($displayEngine, $domainData);
					$displayEngine->setVar('records', $records);

					$displayEngine->display('domain_records.tpl');
				} else {
					$displayEngine->setVar('unknowndomain', $domain);
					$displayEngine->display('unknown_domain.tpl');
				}
			});


			$router->match('POST', '/domain/([^/]+)/delete', function($domain) use ($router, $displayEngine, $api) {
				$this->setVars($displayEngine);

				if (isset($_POST['confirm']) && parseBool($_POST['confirm'])) {
					$result = $api->deleteDomain($domain);

					if (array_key_exists('error', $result)) {
						$displayEngine->flash('error', '', 'There was an error deleting the domain.');
						header('Location: ' . $this->getURL($displayEngine, '/domain/' . $domain ));
						return;
					} else {
						$displayEngine->flash('success', '', 'Domain ' . $domain . ' has been deleted.');
						header('Location: ' . $this->getURL($displayEngine, '/domains'));
						return;
					}
				} else {
					header('Location: ' . $this->getURL($displayEngine, '/domain/' . $domain ));
					return;
				}
			});
		}
	}