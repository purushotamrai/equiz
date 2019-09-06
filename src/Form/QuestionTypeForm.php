<?php

namespace Drupal\equiz\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class QuestionTypeForm.
 */
class QuestionTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $question_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $question_type->label(),
      '#description' => $this->t("Label for the Question type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $question_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\equiz\Entity\QuestionType::load',
      ],
      '#disabled' => !$question_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $question_type = $this->entity;
    $status = $question_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Question type.', [
          '%label' => $question_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Question type.', [
          '%label' => $question_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($question_type->toUrl('collection'));
  }

}
