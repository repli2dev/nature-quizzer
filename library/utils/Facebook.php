<?php

namespace NatureQuizzer\Utils;

use Exception;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookSession;
use Facebook\GraphUser;
use Nette\Application\AbortException;
use Nette\DI\Container;
use Nette\Http\Response;

/**
 * Class Facebook as a simple wrapper around used FB interactions.
 *
 * See: https://developers.facebook.com/docs/php/gettingstarted/4.0.0
 *
 * @package NatureQuizzer\Utils
 */
class Facebook
{
	const FACEBOOK_ERROR = 1;
	const UNKNOWN_ERROR = 2;
	const AFTER_LOGIN_ERROR = 3;
	const NOT_AVAILABLE = 4;

	private $initialized = FALSE;

	private $appId;
	private $appSecret;

	/** @var Response */
	private $response;

	public function __construct(Response $response, Container $container)
	{
		$this->response = $response;

		$params = $container->getParameters();
		if (isset($params['facebook']['appId'])) {
			$this->appId = $params['facebook']['appId'];
		}
		if (isset($params['facebook']['appSecret'])) {
			$this->appSecret = $params['facebook']['appSecret'];
		}

		if ($this->appId && $this->appSecret) {
			FacebookSession::setDefaultApplication($this->appId, $this->appSecret);
			$this->initialized = TRUE;
		}
	}

	private function ensureInitialized()
	{
		if (!$this->initialized) {
			throw new Exception('Facebook authentication is not available at the moment.', self::NOT_AVAILABLE);
		}
	}

	/**
	 * Tries to authenticate user via FB, returns array with name and id on success and throws exception otherwise
	 *
	 * @param $redirectUrl string Absolute URL for FB to redirect user after authentication
	 * @return array Array with data about the user (id, name, email)
	 * @throws Exception if anything goes wrong.
	 */
	public function authenticate($redirectUrl)
	{
		$this->ensureInitialized();

		$helper = new FacebookRedirectLoginHelper($redirectUrl);
		$session = NULL;
		try {
			$session = $helper->getSessionFromRedirect();
		} catch(FacebookRequestException $ex) {
			throw new Exception('Something on Facebook failed.', self::FACEBOOK_ERROR, $ex);
		} catch(Exception $ex) {
			throw new Exception('Something else failed', self::UNKNOWN_ERROR, $ex);
		}
		if ($session) {
			// Logged in
			try {
				$userProfile = (new FacebookRequest(
					$session, 'GET', '/me'
				))->execute()->getGraphObject(GraphUser::className());

				return [
					'name' => $userProfile->getName(),
					'id' => $userProfile->getId(),
					'email' => $userProfile->getProperty('email')
				];
			} catch(FacebookRequestException $e) {
				throw new Exception('Something after login failed.', self::AFTER_LOGIN_ERROR, $e);
			}
		} else {
			// Not logged in
			$loginUrl = $helper->getLoginUrl(
				['scope' => 'email']
			);
			$this->response->redirect($loginUrl);
			throw new AbortException();
		}
	}
} 