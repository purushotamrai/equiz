<?php

namespace Drupal\equiz\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Attempt entities.
 *
 * @ingroup equiz
 */
interface AttemptInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Attempt name.
   *
   * @return string
   *   Name of the Attempt.
   */
  public function getName();

  /**
   * Sets the Attempt name.
   *
   * @param string $name
   *   The Attempt name.
   *
   * @return \Drupal\equiz\Entity\AttemptInterface
   *   The called Attempt entity.
   */
  public function setName($name);

  /**
   * Gets the Attempt creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Attempt.
   */
  public function getCreatedTime();

  /**
   * Sets the Attempt creation timestamp.
   *
   * @param int $timestamp
   *   The Attempt creation timestamp.
   *
   * @return \Drupal\equiz\Entity\AttemptInterface
   *   The called Attempt entity.
   */
  public function setCreatedTime($timestamp);

}
