<?php

namespace Drupal\equiz\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\equiz\AttemptManager;
use Drupal\equiz\AttemptManagerInterface;
use Drupal\equiz\Entity\Attempt;
use Drupal\equiz\Entity\AttemptInterface;
use Drupal\equiz\Entity\QuizInterface;
use Drupal\equiz\Entity\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class EquizAttemptController.
 */
class EquizAttemptController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\equiz\AttemptManagerInterface definition.
   *
   * @var AttemptManagerInterface
   */
  protected $attemptManager;

  /**
   * EquizAttemptController constructor.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   * @param AttemptManagerInterface $attemptManager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AttemptManagerInterface $attemptManager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->attemptManager = $attemptManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('equiz.attempt_manager')
    );
  }

  public function checkCurrentStatus($quiz) {
    // Check equiz user attempt allowed.
    if ($this->attemptManager->userAttemptAllowed($quiz, $this->currentUser(), FALSE)) {
      // Check if existing attempt exists.
      return $this->attemptManager->getExistingAttempt($quiz, $this->currentUser());
    }
    else {
      return AttemptManager::ATTEMPT_NOT_ALLOWED;
    }
  }

  /**
   * Start.
   */
  public function start(QuizInterface $quiz) {
    $existingAttempt = $this->checkCurrentStatus($quiz);

    if ($existingAttempt === AttemptManager::ATTEMPT_NOT_ALLOWED) {
      throw new AccessDeniedHttpException();
    }
    elseif ($existingAttempt === AttemptManager::ATTEMPT_ALLOWED) {
      // Show basic quiz details.
      $entity_type = 'quiz';
      $view_builder = $this->entityTypeManager->getViewBuilder($entity_type);
      $build = $view_builder->view($quiz, 'quiz');
      return $build;
    }
    else {
      // Current Attempt is going on.
      if ($existingAttempt instanceof Attempt) {
        $currentStatus = $existingAttempt->get('field_status')->getString();
        if ($currentStatus == AttemptManager::ATTEMPT_STARTED) {
          $nextQuestion = 1;
        }
        elseif ($currentStatus == AttemptManager::ATTEMPT_PROGRESS) {
          $currentStatus = $existingAttempt->get('field_attempted_questions')->getValue();
          $nextQuestion = count($currentStatus) + 1;
        }
        return new RedirectResponse('/equiz/' . $quiz->id() . '/' . $nextQuestion);
      }
    }

    return [];
  }

  public function buildTimer(AttemptInterface $attempt) {
    $build = [];
    $endTime = $attempt->get('field_end_time')->getString();
    if (!empty($endTime)) {
      $entity_type = 'attempt';
      $view_builder = $this->entityTypeManager->getViewBuilder($entity_type);
      $build = $view_builder->view($attempt, 'quiz');
    }

    return $build;
  }

  /**
   * Process question.
   */
  public function processQuestion(QuizInterface $quiz, $step) {
    $step = (int) $step;
    // Check current status.
    $existingAttempt = $this->checkCurrentStatus($quiz);

    // Allow only if attempt is present in user private temp store.
    if (!($existingAttempt instanceof Attempt)) {
      if ($existingAttempt === AttemptManager::ATTEMPT_NOT_ALLOWED) {
        throw new AccessDeniedHttpException();
      }
      elseif ($existingAttempt === AttemptManager::ATTEMPT_ALLOWED) {
        // Redirect to start page.
        return new RedirectResponse('/equiz/' . $quiz->id() . '/start');
      }
    }

    // Return if step is greater then total questions.
    $totalQuestions = $quiz->get('field_questions')->getValue();
    if ($step > count($totalQuestions)) {
      return new RedirectResponse('/equiz/' . $quiz->id() . '/' . count($totalQuestions));
    }

    $build = [];
    $build['timer'] = [];
    // If step 1 initialize questions.
    if ($step === 1) {
      $this->attemptManager->initializeQuestions($quiz, $existingAttempt);
      $build['question_form'] = $this->formBuilder()->getForm('\Drupal\equiz\Form\EquizQuestionForm', $step, $existingAttempt);
    }

    // If step > 1 check step - 1 is not attempted and do redirect.
    if ($step > 1) {
      $attemptedQuestions = $existingAttempt->get('field_attempted_questions')->getValue();
      if (($step - 1) > count($attemptedQuestions)) {
        return new RedirectResponse('/equiz/' . $quiz->id() . '/' . (count($attemptedQuestions) + 1));
      }
      else {
        // Present Question form.
        $build['question_form'] = $this->formBuilder()->getForm('\Drupal\equiz\Form\EquizQuestionForm', $step, $existingAttempt);
      }
    }

    $quizTimeLimit = $quiz->get('field_time_limit')->getString();
    if (!empty($quizTimeLimit)) {
      if ($this->attemptManager->verifyTimer($existingAttempt)) {
        $build['timer'] = [
          '#prefix' => '<div class="equiz-timer">',
          '#suffix' => '</div>',
        ];
        $build['timer']['title'] = [
          '#markup' => '<div class="equiz-timer-title">' . $this->t('Time Left') . '</div>',
        ];
        $build['timer']['value'] = $this->buildTimer($existingAttempt);
      }
      else {
        return new RedirectResponse('/equiz/' . $quiz->id() . '/timeover/' . $existingAttempt->id());
      }
    }

    $build['#attached']['library'][] = 'equiz/equiz.quiz';

    return $build;
  }

  /**
   * Review.
   */
  public function review(QuizInterface $quiz) {
    // Check current status.
    $existingAttempt = $this->checkCurrentStatus($quiz);

    // Allow only if attempt is present in user private temp store.
    if (!($existingAttempt instanceof Attempt)) {
      if ($existingAttempt === AttemptManager::ATTEMPT_NOT_ALLOWED) {
        throw new AccessDeniedHttpException();
      }
      elseif ($existingAttempt === AttemptManager::ATTEMPT_ALLOWED) {
        // Redirect to start page.
        return new RedirectResponse('/equiz/' . $quiz->id() . '/start');
      }
    }

    $build = [];
    $build['timer'] = [];

    // Check if existingAttempt has all questions.
    $attemptedQuestions = $existingAttempt->get('field_attempted_questions')->getValue();
    $totalQuestions = $quiz->get('field_question_count')->getString();
    if (count($attemptedQuestions) === (int) $totalQuestions) {
      // Show review page
      $build['review_form'] = $this->formBuilder()->getForm('\Drupal\equiz\Form\EquizReviewForm', $existingAttempt);
    }
    else {
      // Redirect to last attempted question.
      return new RedirectResponse('/equiz/' . $quiz->id() . '/' . (count($attemptedQuestions) + 1));
    }

    $quizTimeLimit = $quiz->get('field_time_limit')->getString();
    if (!empty($quizTimeLimit)) {
      if ($this->attemptManager->verifyTimer($existingAttempt)) {
        $build['timer'] = [
          '#prefix' => '<div class="equiz-timer">',
          '#suffix' => '</div>',
        ];
        $build['timer']['title'] = [
          '#markup' => '<div class="equiz-timer-title">' . $this->t('Time Left') . '</div>',
        ];
        $build['timer']['value'] = $this->buildTimer($existingAttempt);
      }
      else {
        return new RedirectResponse('/equiz/' . $quiz->id() . '/timeover/' . $existingAttempt->id());
      }
    }

    $build['#attached']['library'][] = 'equiz/equiz.quiz';

    return $build;
  }
  /**
   * Finish.
   */
  public function finish(QuizInterface $quiz, AttemptInterface $attempt) {
    $account = $this->currentUser();
    $completedAttempts = $this->attemptManager->getCompletedAttempts($quiz, $account);

    // If the attempt is valid.
    $build = [];
    if (!empty($completedAttempts[$attempt->id()])) {
      // Load Quiz Result score.
      $result = $this->attemptManager->getAttemptResult($quiz, $attempt, $account);

      if (!empty($result)) {
        $result = Result::load($result);
        $score = $result->get('field_score')->getString();
        $build[] = [
          '#type' => 'markup',
          '#markup' => $this->t('You have successfully completed the quiz'),
        ];
        $build[] = [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $this->t('Your Score: @score', ['@score' => $score]),
        ];
      }
    }

    return $build;
  }

  /**
   * Time over.
   */
  public function timeOver($quiz, AttemptInterface $attempt) {
    $account = $this->currentUser();
    $completedAttempts = $this->attemptManager->getCompletedAttempts($quiz, $account);

    // If the attempt is valid.
    $build = [];
    if (!empty($completedAttempts[$attempt->id()])) {
      // Load Quiz Result score.
      $result = $this->attemptManager->getAttemptResult($quiz, $attempt, $account);

      if (!empty($result)) {
        $result = Result::load($result);
        $score = $result->get('field_score')->getString();
        $build[] = [
          '#type' => 'markup',
          '#markup' => $this->t('Your quiz was auto submitted successfully after completion of time limit.'),
        ];
        $build[] = [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $this->t('Your Score: @score', ['@score' => $score]),
        ];
      }
    }

    return $build;
  }

}
