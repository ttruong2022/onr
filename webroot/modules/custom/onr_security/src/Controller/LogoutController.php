<?php

namespace Drupal\onr_security\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LogoutController extends ControllerBase {

  public function logout(): RedirectResponse {
    user_logout();
    return $this->redirect('user.login', ['onr' => 'letmein'], ['query' => ['logout' => 1]]);
  }

}
