<?php

namespace Drupal\equiz\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Question entities.
 *
 * @ingroup equiz
 */
interface QuestionInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Question name.
   *
   * @return string
   *   Name of the Question.
   */
  public function getName();

  /**
   * Sets the Question name.
   *
   * @param string $name
   *   The Question name.
   *
   * @return \Drupal\equiz\Entity\QuestionInterface
   *   The called Question entity.
   */
  public function setName($name);

  /**
   * Gets the Question creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Question.
   */
  public function getCreatedTime();

  /**
   * Sets the Question creation timestamp.
   *
   * @param int $timestamp
   *   The Question creation timestamp.
   *
   * @return \Drupal\equiz\Entity\QuestionInterface
   *   The called Question entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Question revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Question revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\equiz\Entity\QuestionInterface
   *   The called Question entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Question revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Question revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\equiz\Entity\QuestionInterface
   *   The called Question entity.
   */
  public function setRevisionUserId($uid);

}
