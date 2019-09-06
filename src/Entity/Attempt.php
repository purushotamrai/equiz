<?php

namespace Drupal\equiz\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Attempt entity.
 *
 * @ingroup equiz
 *
 * @ContentEntityType(
 *   id = "attempt",
 *   label = @Translation("Attempt"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\equiz\AttemptListBuilder",
 *     "views_data" = "Drupal\equiz\Entity\AttemptViewsData",
 *     "translation" = "Drupal\equiz\AttemptTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\equiz\Form\AttemptForm",
 *       "add" = "Drupal\equiz\Form\AttemptForm",
 *       "edit" = "Drupal\equiz\Form\AttemptForm",
 *       "delete" = "Drupal\equiz\Form\AttemptDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\equiz\AttemptHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\equiz\AttemptAccessControlHandler",
 *   },
 *   base_table = "attempt",
 *   data_table = "attempt_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer attempt entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/equiz/structure/attempt/{attempt}",
 *     "add-form" = "/admin/equiz/structure/attempt/add",
 *     "edit-form" = "/admin/equiz/structure/attempt/{attempt}/edit",
 *     "delete-form" = "/admin/equiz/structure/attempt/{attempt}/delete",
 *     "collection" = "/admin/equiz/structure/attempt",
 *   },
 *   field_ui_base_route = "attempt.settings"
 * )
 */
class Attempt extends ContentEntityBase implements AttemptInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Attempt entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the Attempt is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
