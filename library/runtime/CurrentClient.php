<?php
namespace NatureQuizzer\Runtime;

use Nette\Http\Request;
use Nette\Object;
use UAParser\Parser;

class CurrentClient extends Object
{
	/** @var Request */
	private $request;
	/** @var Parser */
	private $agentParser;

	public function __construct(Request $httpRequest)
	{
		$this->request = $httpRequest;
		$this->agentParser = Parser::create();
	}

	public function get()
	{
		$result = $this->agentParser->parse($this->request->getHeader('User-Agent'));
		$result->os->toString();
		$output = [
			'ua' => $result->originalUserAgent,
			'ip' => $this->request->getRemoteAddress(),
			'os_family' => $result->os->family,
			'os_version' => $result->os->toVersion(),
			'browser_family' => $result->ua->family,
			'browser_version' => $result->ua->toVersion(),
			'device' => $result->device->family,
			'resolution' => null,
			'viewport' => null,
			'accept_language' => $this->request->getHeader('Accept-Language')
		];
		return $output;
	}

}