<?php

namespace Drupal\equiz\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\equiz\Entity\AttemptInterface;
use Drupal\equiz\Entity\Quiz;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\equiz\AttemptManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class EquizQuestionForm.
 */
class EquizQuestionForm extends FormBase {

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
   * Constructs a new EquizQuestionForm object.
   */
  public function __construct(
    AttemptManagerInterface $equiz_attempt_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->equizAttemptManager = $equiz_attempt_manager;
    $this->entityTypeManager = $entity_type_manager;
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
    return 'equiz_question_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $step = 1, AttemptInterface $attempt = NULL) {
    // Fetch step question.
    $question = $this->equizAttemptManager->fetchStepQuestion($step, $attempt);
    $entity_type = 'question';
    $view_builder = $this->entityTypeManager->getViewBuilder($entity_type);
    $build = $view_builder->view($question, 'quiz');

    $form_state->setFormState( [
      'question' => $question,
      'attempt' => $attempt,
      'step' => $step,
    ]);

    $form['step'] = [
      '#markup' => $this->t('Question @num:', ['@num' => $step]),
    ];
    $form['question'] = $build;

    $form['options'] = [
      '#type' => 'radios',
      '#title' => $this->t('Options'),
      '#description' => $this->t('Select correct option'),
      '#weight' => '0',
//      '#ajax' => [
//        'callback' => '::saveAttempt',
//        'disable-refocus' => FALSE, // Or TRUE to prevent re-focusing on the triggering element.
//        'event' => 'change',
//        'progress' => [
//          'type' => 'throbber',
//          'message' => $this->t('Saving...'),
//        ],
//      ]
    ];

    $form['options']['#options'] = $this->equizAttemptManager->buildQuestionOptions($question, $attempt);
    $userAnswer = $this->equizAttemptManager->fetchStepUserAnswer($step, $attempt);
    if (isset($userAnswer) && $userAnswer !== '') {
      $form['options']['#default_value'] = 'option_' . $userAnswer;
    }

    // Mark as doubtful.
    $form['doubtful'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mark as doubtful'),
      '#description' => $this->t('Mark as doubtful so to review later'),
      '#default_value' => $this->equizAttemptManager->getMarkedQuestions($attempt, $step),
    ];

    $form['next'] = $this->buildNextButton($attempt, $step);
    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#weight' => 7,
      '#submit' => [
        '::saveAttempt',
      ]
    ];
    // Just for good UX show skip.
    $form['skip'] = $this->buildSkipButton($attempt, $step);

    $form['reset'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#submit' => [
        '::resetAttempt'
      ],
      '#weight' => 9,
    );

    $progressMarkup = $this->equizAttemptManager->buildProgressMarkup($attempt, $step);
    if ($progressMarkup) {
      $form['progress'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Your Progress'),
        '#description' => $this->t('Use Question links to navigate around questions.'),
        '#suffix' => '<em>' . $this->t('Remember to save your answer before navigating directly to any other question.') . '</em>',
        '#weight' => 10,
      ];
      $form['progress']['steps'] = [
        '#markup' => $progressMarkup,
        '#prefix' => '<div class="equiz-progress">',
        '#suffix' => '</div>',
      ];
    }

    $form['time_wasted'] = [
      '#type' => 'hidden',
      '#default_value' => 0,
    ];

    return $form;
  }

  protected function buildNextButton(AttemptInterface $attempt, $currentStep) {
    $next = [];
    $quiz = Quiz::load($attempt->get('field_quiz')->getString());
    if ($quiz instanceof Quiz) {
      $totalQuestions = $quiz->get('field_question_count')->getString();
      if ($currentStep === (int) $totalQuestions) {
        // Review button.
        $next = [
          '#type' => 'submit',
          '#value' => $this->t('Save & Proceed to Review'),
          '#weight' => 7,
          '#submit' => [
            '::saveAttempt',
            '::nextReview',
          ]
        ];
      }
      else {
        // Next Button.
        $next = [
          '#type' => 'submit',
          '#value' => $this->t('Save & Next'),
          '#weight' => 7,
          '#submit' => [
            '::saveAttempt',
            '::nextStep',
          ]
        ];
      }
    }

    return $next;
  }

  protected function buildSkipButton(AttemptInterface $attempt, $currentStep) {
    $skip = [];
    $quiz = Quiz::load($attempt->get('field_quiz')->getString());
    if ($quiz instanceof Quiz) {
      $totalQuestions = $quiz->get('field_question_count')->getString();
      if ($currentStep === (int) $totalQuestions) {
        // Skip & Review button.
        $skip = [
          '#type' => 'submit',
          '#value' => $this->t('Skip & Review'),
          '#weight' => 8,
          '#submit' => [
            '::skipQuestion',
            '::nextReview',
          ],
        ];
      }
      else {
        // Skip Button.
        $skip = [
          '#type' => 'submit',
          '#value' => $this->t('Skip'),
          '#weight' => 8,
          '#submit' => [
            '::skipQuestion',
            '::nextStep',
          ],
        ];
      }
    }

    return $skip;
  }

  public function saveAttempt(array &$form, FormStateInterface $form_state) {
    // Save attempt.
    $raw_user_input = $form_state->getValue('options');
    $question = $form_state->get('question');
    $attempt = $form_state->get('attempt');
    $step = $form_state->get('step');

    $user_input = '';
    if (preg_match('/^option_(\d*)/', $raw_user_input, $matches)) {
      $user_input = $matches[1];
    }

    // Check if attempted_question already exists.
    $attempted_questions = $attempt->get('field_attempted_questions')->getValue();
    if (empty($attempted_questions)) {
      $this->equizAttemptManager->createNewQuestionAttempt($question, $user_input, $attempt);
    }
    else {
      $this->equizAttemptManager->updateExistingQuestionAttempt($step, $question, $user_input, $attempt, $attempted_questions);
    }

    // Save doubtful state.
    $this->equizAttemptManager->markStepQuestion($step, $attempt, $form_state->getValue('doubtful'));

    return $form;
  }

  public function skipQuestion(array &$form, FormStateInterface $formState) {
    $formState->setValue('options', '');
    $this->saveAttempt($form, $formState);
  }

  public function nextStep(&$form, FormStateInterface $formState) {
    $attempt = $formState->get('attempt');
    $step = $formState->get('step');
    $quiz_id = $attempt->get('field_quiz')->getString();

    // Move forward.
    $formState->setRedirect('equiz.equiz_attempt_controller_process_question',
      [
        'quiz' => $quiz_id,
        'step' => $step + 1,
      ]
    );
  }

  public function resetAttempt(array &$form, FormStateInterface $formState) {
    $formState->setValue('options', '');
    $this->saveAttempt($form, $formState);
  }

  public function nextReview(array &$form, FormStateInterface $formState) {
    $attempt = $formState->get('attempt');
    $quiz_id = $attempt->get('field_quiz')->getString();

    // Move forward to review.
    $formState->setRedirect('equiz.equiz_attempt_controller_review',
      [
        'quiz' => $quiz_id,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $timeWasted = (int) $form_state->getValue('time_wasted');
    $attempt = $form_state->get('attempt');

    if ($timeWasted > 0) {
      $this->equizAttemptManager->considerWastedTime($attempt, $timeWasted);
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
