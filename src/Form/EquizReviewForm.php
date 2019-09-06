<?php

namespace Drupal\equiz\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\equiz\AttemptManager;
use Drupal\equiz\Entity\AttemptInterface;
use Drupal\equiz\Entity\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\equiz\AttemptManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class EquizReviewForm.
 */
class EquizReviewForm extends FormBase {

  /**
   * Drupal\equiz\AttemptManagerInterface definition.
   *
   * @var \Drupal\equiz\AttemptManagerInterface
   */
  protected $equizAttemptManager;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EquizReviewForm object.
   */
  public function __construct(
    AttemptManagerInterface $equiz_attempt_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->equizAttemptManager = $equiz_attempt_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('equiz.attempt_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'equiz_review_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AttemptInterface $attempt = NULL) {
    $form_state->setFormState( [
      'attempt' => $attempt,
    ]);

    $attemptStatus = $this->equizAttemptManager->reviewAttemptStatus($attempt);
    if (!empty($attemptStatus)) {
      $form['meta_info'] = [
        '#type' => 'fieldset',
        '#title' => '<h3 class="review-overview">' . $this->t('Overview') . '</h3>',
      ];
      $form['meta_info']['total_questions'] = [
        '#type' => 'label',
        '#title' => $this->t('Total Questions: ')
      ];
      $form['meta_info']['total_questions_value'] = [
        '#plain_text' => $attemptStatus['total_questions'],
        '#suffix' => '</br>',
      ];
      $form['meta_info']['total_answered'] = [
        '#type' => 'label',
        '#title' => $this->t('Total Answered: ')
      ];
      $form['meta_info']['total_answered_value'] = [
        '#plain_text' => count($attemptStatus['total_answered']),
        '#suffix' => '</br>',
      ];
      $form['meta_info']['total_skipped'] = [
        '#type' => 'label',
        '#title' => $this->t('Total Skipped: ')
      ];
      $form['meta_info']['total_skipped_value'] = [
        '#plain_text' => count($attemptStatus['total_skipped']),
        '#suffix' => '</br>',
      ];
      $form['meta_info']['total_marked'] = [
        '#type' => 'label',
        '#title' => $this->t('Marked Questions: ')
      ];
      $form['meta_info']['total_marked_value'] = [
        '#plain_text' => count($attemptStatus['total_marked']),
        '#suffix' => '<hr>',
      ];


      if (count($attemptStatus['total_skipped'])) {
        $form['skipped_questions'] = [
          '#type' => 'fieldset',
          '#title' => '<h4 class="review-overview">' . $this->t('Skipped Questions') . '</h4>',
        ];
        $quiz_id = $attempt->get('field_quiz')->getString();
        foreach ($attemptStatus['total_skipped'] as $qNo) {
          $form['skipped_questions']['question_'. $qNo] = [
            '#markup' => Link::createFromRoute(
              $this->t('Que @no', ['@no' => $qNo]),
              'equiz.equiz_attempt_controller_process_question',
              [
                'quiz' => $quiz_id,
                'step' => $qNo,
              ]
            )->toString()->__toString(),
            '#suffix' => '</br>',
          ];
        }
      }
      else {
        $progressMarkup = $this->equizAttemptManager->buildProgressMarkup($attempt, 'review');
        if ($progressMarkup) {
          $form['progress'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Attempted Questions'),
            '#description' => $this->t('Use Question links to navigate around questions.'),
          ];
          $form['progress']['steps'] = [
            '#markup' => $progressMarkup,
            '#prefix' => '<div class="equiz-progress">',
            '#suffix' => '</div>',
          ];
        }
      }

      if (!empty($attemptStatus['total_marked'])) {
        $form['marked_questions'] = [
          '#type' => 'fieldset',
          '#title' => '<h4 class="review-overview marked-questions">' . $this->t('Marked Questions') . '</h4>',
        ];
        $quiz_id = $attempt->get('field_quiz')->getString();
        foreach ($attemptStatus['total_marked'] as $qNo) {
          $form['marked_questions']['question_'. $qNo] = [
            '#markup' => Link::createFromRoute(
              $this->t('Que @no', ['@no' => $qNo]),
              'equiz.equiz_attempt_controller_process_question',
              [
                'quiz' => $quiz_id,
                'step' => $qNo,
              ]
            )->toString()->__toString(),
            '#suffix' => '</br>',
          ];
        }
      }

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Finish'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Mark attempt as completed.
    $attempt = $form_state->get('attempt');

    if ($attempt instanceof AttemptInterface) {
      $attempt->set('field_status', AttemptManager::ATTEMPT_COMPLETED);
      $attempt->save();

      // Create Quiz Result.
      $result = $this->equizAttemptManager->prepareQuizAttemptResult($attempt);
      if ($result instanceof Result) {
        $quiz_id = $result->get('field_quiz')->getString();
        $this->equizAttemptManager->performCleanup($attempt);

        $form_state->setRedirect('equiz.equiz_attempt_controller_finish',
          [
            'quiz' => $quiz_id,
            'attempt' => $attempt->id(),
          ]
        );
      }
      else {
        $this->messenger->addError($this->t('There is some error submitting result, Contact site administrator.'));
      }
    }
  }

}
