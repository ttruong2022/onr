<?php

namespace Drupal\onr_security\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber that handles cloning through the Replicate module.
 */
class LogoutEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Construct the logout subscriber instance.
   */
  public function __construct(MessengerInterface $messenger, RequestStack $requestStack) {
    $this->messenger = $messenger;
    $this->requestStack = $requestStack;
  }

  public function onRequest(RequestEvent $event) {
    $current_request = $this->requestStack->getCurrentRequest();
    $queryLogout = $current_request->query->get('logout');
    $referer = $current_request->headers->get('referer');

    if (!empty($queryLogout) && $queryLogout == 1 && !str_contains($referer, '?logout=1')) {
      $this->messenger->addStatus($this->t('You have successfully logged out of the system.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['onRequest', 100];
    return $events;
  }

}
