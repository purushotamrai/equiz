<?php

namespace Drupal\equiz;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Attempt entity.
 *
 * @see \Drupal\equiz\Entity\Attempt.
 */
class AttemptAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\equiz\Entity\AttemptInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished attempt entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published attempt entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit attempt entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete attempt entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add attempt entities');
  }

}
