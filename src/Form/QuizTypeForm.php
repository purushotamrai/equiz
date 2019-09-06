<?php

namespace Drupal\equiz\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class QuizTypeForm.
 */
class QuizTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $quiz_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $quiz_type->label(),
      '#description' => $this->t("Label for the Quiz type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $quiz_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\equiz\Entity\QuizType::load',
      ],
      '#disabled' => !$quiz_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $quiz_type = $this->entity;
    $status = $quiz_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Quiz type.', [
          '%label' => $quiz_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Quiz type.', [
          '%label' => $quiz_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($quiz_type->toUrl('collection'));
  }

}
