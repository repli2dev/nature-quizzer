<?php
namespace NatureQuizzer\Processors;

use NatureQuizzer\Runtime\CurrentClient;
use NatureQuizzer\Runtime\CurrentUser;
use NatureQuizzer\Utils\Helpers;
use Nette\DI\Container;
use Nette\Forms\Form;
use Nette\Http\Request;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Object;

class FeedbackProcessor extends Object
{

	/** @var IMailer */
	private $mailer;
	/** @var CurrentClient */
	private $currentClient;
	/** @var CurrentUser */
	private $currentUser;
	/** @var Request */
	private $request;

	private $mails;

	public function __construct(IMailer $mailer, CurrentClient $currentClient, CurrentUser $currentUser, Request $request, Container $container)
	{
		$this->mailer = $mailer;
		$this->currentClient = $currentClient;
		$this->request = $request;
		$this->currentUser = $currentUser;

		$params = $container->getParameters();
		if (isset($params['mails'])) {
			$this->mails = $params['mails'];
		}
	}

	public function send($data)
	{
		$server = $this->request->getUrl()->getHost();

		$output = [];
		$form = $this->getContactForm();
		$form->setValues($data);
		if (!$form->isSuccess()) {
			$output['errors'] = $form->getErrors();
			$output['status'] = 'fail';
		} else {
			$message = new Message();
			$message->setSubject(sprintf('Nature Quizzer (%s): Feedback', $server));
			if (isset($data['email']) && $data['email']) {
				$message->setFrom($data['email']);
			} else {
				$message->setFrom($this->mails['feedback']);
			}
			$message->addTo($this->mails['feedback']);
			$message->setBody($data['text']);
			// Add debug information
			$message->setHeader('X-Client-Info', '"' . Helpers::implodeKeyValue($this->currentClient->get()) . '"');
			if ($this->currentUser->isInitialized()) {
				$message->setHeader('X-User-Anonymous', ($this->currentUser->isAnonymous()) ? 'true' : 'false');
				$message->setHeader('X-User-Id', $this->currentUser->get());
			} else {
				$message->setHeader('X-User', 'unavailable');
			}
			$this->mailer->send($message);
			$output['result'] = 'contact.message_sent';
			$output['status'] = 'success';
		}
		sleep(1); // Prevention of spamming
		return $output;
	}

	private function getContactForm()
	{
		$form = new Form();
		$form->addTextArea('text')
			->addRule(Form::FILLED, 'contact.empty_message');
		$form->addText('email')
			->addCondition(Form::FILLED)
			->addRule(Form::EMAIL, 'contact.wrong_email_format');
		return $form;
	}
}