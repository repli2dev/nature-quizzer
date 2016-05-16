<?php

namespace NatureQuizzer;

use Nette\Application\IRouter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

class RouterFactory
{

	/** @return IRouter */
	public function createRouter()
	{
		$router = new RouteList();
		$router[] = new Route('/sitemap.xml', 'Homepage:sitemap');
		// This part is needed due to the history API to load the proper page when coming to the precise URL
		$router[] = new Route('/offline','Homepage:offline');
		$router[] = new Route('/about','Homepage:about');
		$router[] = new Route('/terms','Homepage:terms');
		$router[] = new Route('/facebook-login-problem','Homepage:facebookLoginProblem');
		$router[] = new Route('/google-login-problem','Homepage:googleLoginProblem');
		$router[] = new Route('/play/<conceptId>[/<codeName>]','Homepage:play');
		$router[] = new Route('/result/<conceptId>','Homepage:result');
		$router[] = new Route('/concepts','Homepage:concepts');
		$router[] = new Route('/user/login','Homepage:userLogin');
		$router[] = new Route('/user/logout','Homepage:userLogout');
		$router[] = new Route('/user/register','Homepage:userRegister');
		// The rest

		$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');
		return $router;
	}

}
