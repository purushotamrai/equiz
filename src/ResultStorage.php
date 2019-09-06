<?php

namespace Drupal\equiz;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\equiz\Entity\ResultInterface;

/**
 * Defines the storage handler class for Result entities.
 *
 * This extends the base storage class, adding required special handling for
 * Result entities.
 *
 * @ingroup equiz
 */
class ResultStorage extends SqlContentEntityStorage implements ResultStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ResultInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {result_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {result_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

}
