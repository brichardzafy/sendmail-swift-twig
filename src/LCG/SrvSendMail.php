<?php
/**
 * Created by PhpStorm .
 * Date: 26/04/2021
 * Time: 18:05
 */

namespace LCG\Lib;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Mail
 * @package LCGLib
 * @author  Brichard.Z<brichard.zafy@gmail.com>
 */
class SrvSendMail
{
    /**
     * @var \Swift_Mailer
     */
    private $_mailer;
    /**
     * @var \Symfony\Bridge\Twig\Form\TwigRendererInterface
     */
    private $_templating;

    private $_sender = "";
    /**
     * @var string
     */
    private $_app_name = "";

    /**
     * SrvSendMail constructor.
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param                                                           $app_name
     */
    public function __construct(ContainerInterface $container,$app_name)
    {
        $transport = (new \Swift_SmtpTransport(
            $container->getParameter("mailer_host"),
            $container->getParameter("mailer_port")))
            ->setUsername($container->getParameter("mailer_email"))
            ->setPassword($container->getParameter("mailer_password")) ;
        $this->_mailer  = new \Swift_Mailer($transport);
        $this->_templating = $container->get('twig');
        $this->_app_name   = $app_name;
        $this->_sender     = $container->getParameter("mailer_sender_name");
    }

    /**
     * @param        $to
     * @param string $subject
     * @param string $template_name
     * @param array  $message_vars
     * @param array  $attachments
     * @return bool
     * @throws \Exception
     */
    public function send($to, string $subject, string $template_path_name,
                         array $message_vars=[],
                         $attachments = []): bool
    {
        $message = (new \Swift_Message($subject))
            ->setFrom($this->getSender(),$this->_app_name)
            ->setTo($to)
            ->setBody(
                $this->_templating->render(
                    $template_path_name,
                    $message_vars
                ),
                'text/html'
            );
        if(count($attachments)>0){
            foreach ($attachments as $attachment) {
                $message->attach(\Swift_Attachment::fromPath($attachment));
            }
        }
        $fail_recipient = [];
        $res= $this->_mailer->send($message,$fail_recipient);
        if(!empty($fail_recipient)){
            print_r($fail_recipient);
            throw new \Exception("Recipient not correct ".print_r($fail_recipient,true));
        }
        return $res;
    }

    /**
     * @return mixed
     */
    public function getSender()
    {
        return $this->_sender;
    }

    /**
     * @param mixed $sender
     * @return \LCGLib\SrvSendMail
     */
    public function setSender($sender)
    {
        $this->_sender = $sender;
        return $this;
    }
}