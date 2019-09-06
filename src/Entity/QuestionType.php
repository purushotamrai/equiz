<?php

namespace Drupal\equiz\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Question type entity.
 *
 * @ConfigEntityType(
 *   id = "question_type",
 *   label = @Translation("Question type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\equiz\QuestionTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\equiz\Form\QuestionTypeForm",
 *       "edit" = "Drupal\equiz\Form\QuestionTypeForm",
 *       "delete" = "Drupal\equiz\Form\QuestionTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\equiz\QuestionTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "question_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "question",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/equiz/question_type/{question_type}",
 *     "add-form" = "/admin/equiz/question_type/add",
 *     "edit-form" = "/admin/equiz/question_type/{question_type}/edit",
 *     "delete-form" = "/admin/equiz/question_type/{question_type}/delete",
 *     "collection" = "/admin/equiz/question_type"
 *   }
 * )
 */
class QuestionType extends ConfigEntityBundleBase implements QuestionTypeInterface {

  /**
   * The Question type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Question type label.
   *
   * @var string
   */
  protected $label;

}
