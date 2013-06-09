<?php
namespace Craft;

class ContactFormController extends BaseController
{
	protected $allowAnonymous = true;

	public function actionSendMessage()
	{
		$this->requirePostRequest();

		$plugin = craft()->plugins->getPlugin('contactform');

		if (!$plugin)
		{
			throw new Exception('Couldn’t find the Contact Form plugin!');
		}

		$settings = $plugin->getSettings();

		if (($toEmail = $settings->toEmail) == null)
		{
			craft()->userSession->setError('The "To Email" address is not set on the plugin’s settings page.');
			Craft::log('Tried to send a contact form request, but missing the "To Email" address on the plugin’s settings page.', LogLevel::Error);
			$this->redirectToPostedUrl();
		}
		else
		{
			$message = new ContactFormModel();

			$message->fromEmail = craft()->request->getPost('fromEmail');
			$message->fromName  = craft()->request->getPost('fromName');
			$message->message   = craft()->request->getPost('message');

			$subject = "New message from ".Craft::getSiteName();

			if (($postedSubject = craft()->request->getPost('subject', null)) !== null)
			{
				$subject .= ' - '.$postedSubject;
			}

			$message->subject = $subject;

			if ($message->validate())
			{
				$email = new EmailModel();

				$email->fromEmail = $message->fromEmail;
				$email->fromName  = $message->fromName;
				$email->toEmail   = $toEmail;
				$email->subject   = $subject;
				$email->body      = $message->message;

				try
				{
					craft()->email->sendEmail($email);
					craft()->userSession->setNotice('Your message has been sent, someone will be in touch shortly!');
					$this->redirectToPostedUrl();
				}
				catch (\phpmailerException $e)
				{
					Craft::log('Tried to send a contact form request, but something terrible happened: '.$e->getMessage(), LogLevel::Error);
					craft()->userSession->setError(Craft::t('Couldn’t send contact email. Check your email settings.'));
					$this->redirectToPostedUrl();
				}
			}
			else
			{
				craft()->userSession->setError('There was a problem with your submission, please check the form and try again!');
			}

			craft()->urlManager->setRouteVariables(array(
				'message' => $message
			));
		}
	}
}
