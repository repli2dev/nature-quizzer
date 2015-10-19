<?php
namespace NatureQuizzer\Processors;

use NatureQuizzer\Runtime\CurrentClient;
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
	/** @var Request */
	private $request;

	private $mails;

	public function __construct(IMailer $mailer, CurrentClient $currentClient, Request $request, Container $container)
	{
		$this->mailer = $mailer;
		$this->currentClient = $currentClient;
		$this->request = $request;

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