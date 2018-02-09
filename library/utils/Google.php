<?php

namespace NatureQuizzer\Utils;

use Exception;
use Google_Client;
use Google_Service_Plus;
use Nette\Application\AbortException;
use Nette\DI\Container;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;

/**
 * Class Google as a simple wrapper around used Google+ interactions.
 *
 * See: https://developers.google.com/+/web/signin/redirect-uri-flow
 *
 * @package NatureQuizzer\Utils
 */
class Google
{
	const NOT_AVAILABLE = 1;

	private $clientId;
	private $clientSecret;

	private $initialized = FALSE;

	/** @var Response */
	private $response;
	/** @var Request */
	private $request;
	/** @var Session */
	private $session;

	private $client;

	public function __construct(Response $response, Request $request, Session $session, Container $container)
	{
		$this->response = $response;
		$this->request = $request;
		$this->session = $session;

		$params = $container->getParameters();
		if (isset($params['google']['clientId'])) {
			$this->clientId = $params['google']['clientId'];
		}
		if (isset($params['google']['clientSecret'])) {
			$this->clientSecret = $params['google']['clientSecret'];
		}

		$this->client = new Google_Client();
		if ($this->clientId && $this->clientSecret) {
			$this->client->setClientId($params['google']['clientId']);
			$this->client->setClientSecret($params['google']['clientSecret']);
			$this->initialized = TRUE;
		}
	}

	private function ensureInitialized()
	{
		if (!$this->initialized) {
			throw new Exception('Google authentication is not available at the moment.', self::NOT_AVAILABLE);
		}
	}

	/**
	 * Tries to authenticate user via Google, returns array with name and id on success and throws exception otherwise
	 *
	 * @param $redirectUrl string Absolute URL for FB to redirect user after authentication
	 * @return array Array with data about the user (id, name, email)
	 * @throws Exception if anything goes wrong.
	 */
	public function authenticate($redirectUrl)
	{
		$this->ensureInitialized();

		$securityToken = $this->getSecurityToken();

		$client = clone $this->client;
		$client->setRedirectUri($redirectUrl);
		$client->addScope([
			'https://www.googleapis.com/auth/plus.login',
			'https://www.googleapis.com/auth/userinfo.email'
		]);
		$client->setState($securityToken);

		$code = $this->request->getQuery('code');
		// If code is present then the user is returning from auth
		if ($code !== NULL) {
			$getToken = $this->request->getQuery('token');
			if (!$securityToken) {
				throw new Exception('Missing [state] token in session, cannot continue.');
			}
			if ($getToken !== NULL && $securityToken !== $getToken) {
				throw new Exception('Invalid [state] token, potential CLRF attack attempt.');
			}

			try {
				// Finish authentication
				$client->authenticate($code);
				// Get your access and refresh tokens, which are both contained in the
				// following response, which is in a JSON structure:
				$oAuthToken = json_decode($client->getAccessToken());
				$data = $client->verifyIdToken($oAuthToken->id_token);

				$plus = new Google_Service_Plus($client);
				$person = $plus->people->get('me');

				// Issue new security token
				$this->resetSecurityToken();

				// Get other data and continue with login
				$output = [
					'name' => $person->getDisplayName(),
					'email' => $data->getAttributes()['payload']['email'],
					'id' => $data->getUserId()
				];
				return $output;
			} catch (Exception $ex) {
				throw new Exception('Failure when doing authentization and fetching informations from Google Plus.', 0, $ex);
			}

		} else {
			$this->response->redirect($client->createAuthUrl());
			throw new AbortException();
		}
	}

	private function getSecurityToken()
	{
		$section = $this->session->getSection('google');
		if (!$section->offsetExists('token')) {
			$this->resetSecurityToken();
		}
		return $section->offsetGet('token');
	}

	private function resetSecurityToken()
	{
		$section = $this->session->getSection('google');
		$section->offsetSet('token', sha1(time() . rand()));
	}
} 