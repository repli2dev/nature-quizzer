<?php

namespace NatureQuizzer\Utils;

use Exception;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook as FacebookSession;
use Nette\Application\AbortException;
use Nette\DI\Container;
use Nette\Http\Response;

/**
 * Class Facebook as a simple wrapper around used FB interactions.
 *
 * See: https://developers.facebook.com/docs/php/gettingstarted/5.0.0
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

	/** @var FacebookSession */
	private $facebookSession;

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
			$this->facebookSession = new FacebookSession([
				'app_id' =>$this->appId,
				'app_secret' => $this->appSecret,
				'default_graph_version' => 'v2.12',
			]);
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
	 * @param string $redirectUrl Absolute URL for FB to redirect user after authentication
	 * @return array Array with data about the user (id, name, email)
	 * @throws Exception if anything goes wrong.
	 */
	public function authenticate($redirectUrl)
	{
		$this->ensureInitialized();

		$helper = $this->facebookSession->getRedirectLoginHelper();
		$session = NULL;
		try {
			$accessToken = $helper->getAccessToken($redirectUrl);
		} catch(FacebookResponseException $ex) {
			throw new Exception('Something on Facebook failed when performing request.', self::FACEBOOK_ERROR, $ex);
		} catch(FacebookSDKException $ex) {
			throw new Exception('Something on Facebook failed after the request.', self::FACEBOOK_ERROR, $ex);
		} catch(Exception $ex) {
			throw new Exception('Something else failed', self::UNKNOWN_ERROR, $ex);
		}
		if ($accessToken) {
			// Logged in
			try {
				$userProfile = $this->facebookSession->get(
					'/me?fields=email,name',
					$accessToken
				)->getGraphUser();

				return [
					'name' => $userProfile->getName(),
					'id' => $userProfile->getId(),
					'email' => $userProfile->getField('email')
				];
			} catch(FacebookResponseException $ex) {
				throw new Exception('Something after login failed.', self::AFTER_LOGIN_ERROR, $ex);
			} catch(FacebookSDKException $ex) {
				throw new Exception('Something after login failed.', self::AFTER_LOGIN_ERROR, $ex);
			}
		} else {
			// Not logged in
			$loginUrl = $helper->getLoginUrl($redirectUrl, ['email'] /* scope */);
			$this->response->redirect($loginUrl);
			throw new AbortException();
		}
	}
}
