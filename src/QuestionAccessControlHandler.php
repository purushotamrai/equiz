<?php

namespace Drupal\equiz;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Question entity.
 *
 * @see \Drupal\equiz\Entity\Question.
 */
class QuestionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\equiz\Entity\QuestionInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished question entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published question entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit question entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete question entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add question entities');
  }

}
