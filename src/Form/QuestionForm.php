<?php

namespace Drupal\equiz\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Question edit forms.
 *
 * @ingroup equiz
 */
class QuestionForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Constructs a new QuestionForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, AccountProxyInterface $account) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\equiz\Entity\Question $entity */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
    }

    $form['#attached']['library'][] = 'node/drupal.node';
    $form['advanced'] = [
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    ];

    $form['revision_log_message']['#group'] = 'advanced';
    $form['revision_log_message']['#type'] = 'details';
    $form['revision_log_message']['#title'] = $this->t('Revision Information');

    $form['status']['#group'] = 'advanced';
    $form['status']['#type'] = 'details';
    $form['status']['#title'] = $this->t('Status');

    $form['user_id']['#group'] = 'advanced';
    $form['user_id']['#type'] = 'details';
    $form['user_id']['#title'] = $this->t('Author');

    $form['#attached']['library'][] = 'equiz/equiz.admin';

    if (!empty($form['field_option']) && !empty($form['field_correct_option'])) {
      $field_option_delta = $form['field_option']['widget']['#max_delta'];
      $field_correct_option = $form['field_correct_option']['widget'][0]['value']['#default_value'];
      for ($i  = 0; $i <= $field_option_delta; $i++) {
        $form['field_option']['widget'][$i]['correct'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Correct Option'),
          '#weight' => 10,
        ];
      }
      if (!is_null($field_correct_option)) {
        $form['field_option']['widget'][$field_correct_option]['correct']['#default_value'] = 1;
      }
      $form['#validate'][] = '::validateCorrectOption';

      $form['field_correct_option']['widget'][0]['value']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * Set field_correct_option value based on correct checkbox.
   */
  public function validateCorrectOption(array &$form, FormStateInterface $form_state) {
    $field_option_values = $form_state->getValue('field_option');

    // As of now supporting single correct option.
    $field_correct_option = '';
    foreach ($field_option_values as $delta => $values) {
      if (is_numeric($delta) && !empty($values['correct'])) {
        $field_correct_option = $delta;
      }
    }

    // @todo Resolve reordering correct option issue.
    $form_state->setValue('field_correct_option', [['value' => $field_correct_option]]);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Question.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Question.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.question.canonical', ['question' => $entity->id()]);
  }

}
