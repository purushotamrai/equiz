<?php

namespace Drupal\equiz\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Quiz entities.
 *
 * @ingroup equiz
 */
interface QuizInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Quiz name.
   *
   * @return string
   *   Name of the Quiz.
   */
  public function getName();

  /**
   * Sets the Quiz name.
   *
   * @param string $name
   *   The Quiz name.
   *
   * @return \Drupal\equiz\Entity\QuizInterface
   *   The called Quiz entity.
   */
  public function setName($name);

  /**
   * Gets the Quiz creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Quiz.
   */
  public function getCreatedTime();

  /**
   * Sets the Quiz creation timestamp.
   *
   * @param int $timestamp
   *   The Quiz creation timestamp.
   *
   * @return \Drupal\equiz\Entity\QuizInterface
   *   The called Quiz entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Quiz revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Quiz revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\equiz\Entity\QuizInterface
   *   The called Quiz entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Quiz revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Quiz revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\equiz\Entity\QuizInterface
   *   The called Quiz entity.
   */
  public function setRevisionUserId($uid);

}
