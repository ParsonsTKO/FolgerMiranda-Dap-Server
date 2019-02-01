<?php

/**
 * File containing the EmailService class.
 *
 * (c) http://parsonstko.com/
 */

namespace DAPBundle\Services;

use Symfony\Component\DependencyInjection\Container;
use Psr\Log\LoggerInterface;

class EmailService
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    protected $dapEmailLogger;

    /**
     * @var array
     */
    protected $emailSettings;


    public function __construct(Container $container, LoggerInterface $dapEmailLogger = null)
    {
        $this->container = $container;
        $this->dapEmailLogger = $dapEmailLogger;
    }

    public function emailSettings(array $emailSettings = null)
    {
        $this->emailSettings = $emailSettings;
    }

    public function sendEmail( $from = null, $to = null, $subject = null, $body = null)
    {

        $emailFrom = $from ?? $this->emailSettings['email_from'];
        $emailTo = $to ?? $this->emailSettings['email_to'];
        settype($emailFrom, 'string');
        settype($emailTo, 'string');

        $message = new \Swift_Message();
        $message->setSubject($subject ?? $this->emailSettings['email_subject']);
        $message->setFrom($emailFrom);
        $message->setTo($emailTo);
        $message->setBody(
            $this->container->get('templating')->render('DAPBundle:Emails:notification.html.twig', array(
                'body' => $body ?? $this->emailSettings['email_body'],
            )),
            'text/html'
        );


        $this->container->get('mailer')->send($message);

        return $message;
    }

}