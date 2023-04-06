<?php

namespace Drupal\onr_security\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($routeLogout = $collection->get('user.logout')) {
      $routeLogout->setDefaults([
        '_controller' => '\Drupal\onr_security\Controller\LogoutController::logout',
      ]);
    }
  }

}
