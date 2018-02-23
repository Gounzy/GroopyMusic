<?php

namespace AppBundle\Services;


use Twig\Environment;

class UtilitiesExtension extends \Twig_Extension
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('decode_html', array($this, 'decode')),
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('hidden_loader', array($this, 'hidden_loader'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('preview', array($this, 'preview', array('is_safe' => array('html')))),
            new \Twig_SimpleFunction('error', array($this, 'error', array('is_safe' => array('html')))),
            new \Twig_SimpleFunction('email_content', array($this, 'email_content', array('is_safe' => array('html')))),
            new \Twig_SimpleFunction('notification_preview', array($this, 'notification_preview', array('is_safe' => array('html')))),
            new \Twig_SimpleFunction('notification_menu_preview', array($this, 'notification_menu_preview', array('is_safe' => array('html')))),
            new \Twig_SimpleFunction('notification_complete', array($this, 'notification_complete', array('is_safe' => array('html')))),
            new \Twig_SimpleFunction('email_link', array($this, 'email_link', array('is_safe' => array('html')))),
        );
    }

    public function decode($value)
    {
        return strip_tags(html_entity_decode($value));
    }

    public function hidden_loader($hidden = true) {
        return $this->twig->render('patterns/utils/hidden_loader.html.twig', [
            'hidden' => $hidden,
        ]);
    }

    public function preview($text, $limit) {
        return $this->twig->render('patterns/utils/preview.html.twig', [
            'text' => $text,
            'limit' => $limit,
        ]);
    }

    public function error($message, $dismissible = true) {
        return $this->twig->render('patterns/utils/error.html.twig', [
            'message' => $message,
            'dismissible' => $dismissible,
        ]);
    }

    public function notification_preview($notification, $title, $content) {
        return $this->twig->render('AppBundle:Notifications/utils:preview.html.twig', array(
            'notification' => $notification,
            'title' => $title,
            'content' => $content,
        ));
    }

    public function notification_menu_preview($notification, $title, $content) {
        return $this->twig->render('AppBundle:Notifications/utils:menu_preview.html.twig', array(
            'notification' => $notification,
            'title' => $title,
            'content' => $content,
        ));
    }

    public function notification_complete($notification, $title, $content) {
        return $this->twig->render('AppBundle:Notifications/utils:complete.html.twig', array(
            'notification' => $notification,
            'title' => $title,
            'content' => $content,
        ));
    }

    public function email_content($email_key, $email_variables) {
        return $this->twig->render(':patterns/utils:email.html.twig', array(
            'email_key' => $email_key,
            'email_variables' => $email_variables,
        ));
    }

    public function email_link($url, $text = null) {
        if($text == null) {
            $text = $url;
        }
        return $this->twig->render(':patterns/utils:email_link.html.twig', array(
            'url' => $url,
            'text' => $text,
        ));
    }

}