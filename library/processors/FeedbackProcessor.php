<?php
namespace NatureQuizzer\Processors;

use NatureQuizzer\Runtime\CurrentClient;
use Nette\DI\Container;
use Nette\Forms\Form;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Object;

class FeedbackProcessor extends Object
{

	/** @var IMailer */
	private $mailer;
	/** @var CurrentClient */
	private $currentClient;

	private $mails;

	public function __construct(IMailer $mailer, CurrentClient $currentClient, Container $container)
	{
		$this->mailer = $mailer;
		$this->currentClient = $currentClient;

		$params = $container->getParameters();
		if (isset($params['mails'])) {
			$this->mails = $params['mails'];
		}
	}

	public function send($data)
	{
		$output = [];
		$form = $this->getContactForm();
		$form->setValues($data);
		if (!$form->isSuccess()) {
			$output['errors'] = $form->getErrors();
			$output['status'] = 'fail';
		} else {
			$message = new Message();
			$message->setSubject('Nature Quizzer: Feedback');
			$message->setFrom($data['email']);
			$message->addTo($this->mails['feedback']);
			$message->setBody($data['text']);
			$this->mailer->send($message);
			$output['result'] = 'Message was successfully sent. Thank you.';
			$output['status'] = 'success';
		}
		return $output;
	}

	private function getContactForm()
	{
		$form = new Form();
		$form->addTextArea('text')
			->addRule(Form::FILLED, 'Please fill in the content of your message.');
		$form->addText('email')
			->addRule(Form::FILLED, 'Please fill in the e-mail.')
			->addRule(Form::EMAIL, 'E-mail must be in proper format: someone@somewhere.tld.');
		return $form;
	}
}