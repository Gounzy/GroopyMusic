<?php

namespace AppBundle\Services;

use AppBundle\Entity\Notification;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig_Environment as Environment;

class NotificationRenderer
{
    private $twig;
    private $requestStack;

    public function __construct(Environment $twig, RequestStack $requestStack)
    {
        $this->twig = $twig;
        $this->requestStack = $requestStack;
    }

    public function renderNotif(Notification $notification, $locale, $preview = false) {
        $params = array_merge(['notification' => $notification, 'preview' => $preview], $notification->getParams());

        try {
            return $this->twig->render('AppBundle:Notifications:' . $notification->getType() . '.' . $locale . '.html.twig', $params);
        } catch(\Exception $e) {
            return $this->twig->render('AppBundle:Notifications:' . $notification->getType() . '.' . $this->requestStack->getCurrentRequest()->getDefaultLocale() . '.html.twig', $params);
        }
    }
}