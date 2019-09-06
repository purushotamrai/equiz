<?php

namespace Drupal\equiz;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface ResultStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Result revision IDs for a specific Result.
   *
   * @param \Drupal\equiz\Entity\ResultInterface $entity
   *   The Result entity.
   *
   * @return int[]
   *   Result revision IDs (in ascending order).
   */
  public function revisionIds(ResultInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Result author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Result revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

}
