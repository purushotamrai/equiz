<?php

namespace Drupal\equiz\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Quiz type entity.
 *
 * @ConfigEntityType(
 *   id = "quiz_type",
 *   label = @Translation("Quiz type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\equiz\QuizTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\equiz\Form\QuizTypeForm",
 *       "edit" = "Drupal\equiz\Form\QuizTypeForm",
 *       "delete" = "Drupal\equiz\Form\QuizTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\equiz\QuizTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "quiz_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "quiz",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/equiz/quiz_type/{quiz_type}",
 *     "add-form" = "/admin/equiz/quiz_type/add",
 *     "edit-form" = "/admin/equiz/quiz_type/{quiz_type}/edit",
 *     "delete-form" = "/admin/equiz/quiz_type/{quiz_type}/delete",
 *     "collection" = "/admin/equiz/quiz_type"
 *   }
 * )
 */
class QuizType extends ConfigEntityBundleBase implements QuizTypeInterface {

  /**
   * The Quiz type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Quiz type label.
   *
   * @var string
   */
  protected $label;

}
