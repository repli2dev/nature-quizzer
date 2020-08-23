<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace NatureQuizzer\Utils;
use Nette\Application\IResponse;
use Nette\Http\IResponse as IHttpResponse;
use Nette\Http\IRequest;
use Nette\SmartObject;


/**
 * XML response
 *
 * @property-read array|\stdClass $payload
 * @property-read string $contentType
 */
class XmlResponse implements IResponse
{
	use SmartObject;

	/** @var string */
	private $payload;

	/** @var string */
	private $contentType;


	/**
	 * @param  string  payload
	 * @param  string    MIME content type
	 */
	public function __construct($payload, $contentType = NULL)
	{
		$this->payload = $payload;
		$this->contentType = $contentType ? $contentType : 'application/xml';
	}


	/**
	 * @return array|\stdClass
	 */
	public function getPayload()
	{
		return $this->payload;
	}


	/**
	 * Returns the MIME content type of a downloaded file.
	 * @return string
	 */
	public function getContentType()
	{
		return $this->contentType;
	}


	/**
	 * Sends response to output.
	 * @return void
	 */
	public function send(IRequest $httpRequest, IHttpResponse $httpResponse): void
	{
		$httpResponse->setContentType($this->contentType);
		$httpResponse->setExpiration(FALSE);
		echo $this->payload;
	}

}
