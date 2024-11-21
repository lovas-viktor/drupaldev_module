<?php

namespace Drupal\drupaldev_simple_pass_reset\Controller;

use Drupal\Component\Utility\Crypt;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Controller\UserController;

/**
 * Alter User Reset password page. 
 */
class User extends UserController{

  /**
   * Displays user password reset form.
   */
  public function resetPass(Request $request, $uid, $timestamp, $hash) {
      /** @var \Drupal\user\UserInterface $user */
      $user = $this->userStorage->load($uid);
      if ($redirect = $this->determineErrorRedirect($user, $timestamp, $hash)) {
          return $redirect;
      }

      $flood_config = $this->config('user.flood');
      if ($flood_config->get('uid_only')) {
          $identifier = $user->id();
      }
      else {
          $identifier = $user->id() . '-' . $request->getClientIP();
      }

      $this->flood->clear('user.failed_login_user', $identifier);
      $this->flood->clear('user.http_login', $identifier);

      user_login_finalize($user);
      $this->logger->info('User %name used one-time login link at time %timestamp.', ['%name' => $user->getDisplayName(), '%timestamp' => $timestamp]);
      $this->messenger()->addStatus($this->t('You have just used your one-time login link. It is no longer necessary to use this link to log in. It is recommended that you set your password.'));
      // Let the user's password be changed without the current password
      // check.
      $token = Crypt::randomBytesBase64(55);
      $request->getSession()->set('pass_reset_' . $user->id(), $token);
      // Clear any flood events for this user.
      $this->flood->clear('user.password_request_user', $uid);
      return $this->redirect('change_pwd_page.change_password');
  }

}
