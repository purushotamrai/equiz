<?php

namespace Drupal\equiz\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\equiz\AttemptManager;
use Drupal\equiz\AttemptManagerInterface;
use Drupal\equiz\Entity\QuizInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class StartQuiz.
 */
class StartQuiz extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\equiz\AttemptManager definition.
   *
   * @var AttemptManager
   */
  protected $attemptManager;

  /**
   * Constructs a new StartQuiz object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AttemptManagerInterface $attemptManager,
    RouteMatchInterface $routeMatch
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->attemptManager = $attemptManager;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('equiz.attempt_manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'start_quiz';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $quiz = $this->routeMatch->getParameter('quiz');

    $userAttemptAllowed = $this->attemptManager->userAttemptAllowed($quiz);
    if ($quiz instanceof QuizInterface && $userAttemptAllowed) {
      $form['quiz_id'] = [
        '#type' => 'hidden',
        '#value' => $quiz->id(),
      ];
      $form['user'] = [
        '#type' => 'hidden',
        '#value' => $this->currentUser()->id(),
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Start Quiz'),
      ];
    }
    else {
      $this->messenger()->addWarning('Kindly visit contests page to join the contest');
      $form['not_allowed'] = [
        '#markup' => $this->t('Kindly visit contests page to join the contest'),
      ];
    }

    if ($userAttemptAllowed == AttemptManager::ATTEMPT_TIME_OVER) {
      $this->messenger()->addError('Kindly visit contests page to join the contest');
      $form['time_over'] = $this->t('This contest is over now');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $quiz = $this->routeMatch->getParameter('quiz');

    $userAttemptAllowed = $this->attemptManager->userAttemptAllowed($quiz);

    if ($userAttemptAllowed && $quiz instanceof QuizInterface) {
      $this->attemptManager->initializeAttempt($quiz);
      $form_state->setRedirect('equiz.equiz_attempt_controller_process_question',
        [
          'quiz' => $quiz->id(),
          'step' => 1
        ]
      );
    }
  }

}
