<?php

namespace Drupal\equiz;
use DateTime;
use DateTimeZone;
use Drupal\Component\Datetime\Time;
use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\equiz\Entity\Attempt;
use Drupal\equiz\Entity\AttemptInterface;
use Drupal\equiz\Entity\Question;
use Drupal\equiz\Entity\QuestionInterface;
use Drupal\equiz\Entity\Quiz;
use Drupal\equiz\Entity\QuizInterface;
use Drupal\equiz\Entity\Result;
use Drupal\equiz\Entity\ResultInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\user\Entity\User;

/**
 * Class AttemptManager.
 */
class AttemptManager implements AttemptManagerInterface {

  const ATTEMPT_ALLOWED = 1;
  const ATTEMPT_NOT_ALLOWED = 0;
  const ATTEMPT_TIME_OVER = -1;

  const ATTEMPT_STARTED = 3;
  const ATTEMPT_PROGRESS = 4;
  const ATTEMPT_COMPLETED = 5;

  /**
   * Drupal\Core\TempStore\PrivateTempStoreFactory definition.
   *
   * @var PrivateTempStoreFactory
   */
  protected $tempStorePrivate;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\equiz\TimeManagerInterface Defintion.
   *
   * @var TimeManager
   */
  protected $timeManager;

  /**
   * Constructs a new AttemptManager object.
   */
  public function __construct(PrivateTempStoreFactory $tempstore_private, AccountProxyInterface $currentUser, TimeManagerInterface $timeManager) {
    $this->tempStorePrivate = $tempstore_private->get('equiz');
    $this->currentUser = $currentUser;
    $this->timeManager = $timeManager;
  }

  public function userAttemptAllowed(QuizInterface $quiz, $user = NULL, $attemptQuizPermit = TRUE) {
    if ($user) {
      $account = $user;
    }
    else {
      $account = $this->currentUser;
    }

    // Confirm attempt equiz permissions.
    if (!$attemptQuizPermit) {
      if (!$account->hasPermission('attempt equiz')) {
        return self::ATTEMPT_NOT_ALLOWED;
      }
    }

    // Check user is a valid participant.
    if (!empty($quiz->get('field_participants')->getValue())) {
      $participants = explode(', ', $quiz->get('field_participants')->getString());
      if (!in_array($account->id(), $participants)) {
        return self::ATTEMPT_NOT_ALLOWED;
      }
    }
    else {
      // If it's not mock quiz return not allowed.
      if (empty($quiz->get('field_mock_quiz')->getString())) {
        return self::ATTEMPT_NOT_ALLOWED;
      }
    }

    // Check time restrictions also.
    return $this->quizAttemptAllowed($quiz);
  }

  /**
   * Check if quiz attempt allowed based on time.
   *
   * @param QuizInterface $quiz
   * @return int
   */
  public function quizAttemptAllowed(QuizInterface $quiz) {
    $currentTime =  \Drupal::time()->getCurrentTime();
    $timeZone = new DateTimeZone('UTC');

    if (!empty($quiz->get('field_start_date'))) {
      $startTime = $quiz->get('field_start_date')->getString();
      $startTime = new DateTime($startTime, $timeZone);
      $startTime = $startTime->getTimestamp();

      // Check if quiz can be started.
      if ($currentTime >= $startTime) {
        // Check if quiz has not ended.
        if (!empty($quiz->get('field_end_date'))) {
          $endTime = $quiz->get('field_end_date')->getString();
          $endTime = new DateTime($endTime, $timeZone);
          $endTime = $endTime->getTimestamp();
          if ($currentTime <= $endTime) {
            $allowed = self::ATTEMPT_ALLOWED;
          }
          else {
            $allowed = self::ATTEMPT_TIME_OVER;
          }
        }
        else {
          $allowed = self::ATTEMPT_ALLOWED;
        }
      }
      else {
        $allowed = self::ATTEMPT_NOT_ALLOWED;
      }
    }
    else {
      $allowed = self::ATTEMPT_ALLOWED;
    }

    return $allowed;
  }

  public function initializeAttempt(QuizInterface $quiz) {
    // Create Attempt Entity and store the information in user private temp store.
    $attempt = Attempt::create([
      'status' => 1,
      'name' => \Drupal::time()->getCurrentTime(),
      'field_quiz' => $quiz->id(),
      'field_status' => 3,
      'field_user' => $this->currentUser->id(),
    ]);
    $attempt->save();
    $this->tempStorePrivate->set('equiz:' . $quiz->id() . ':attempt', $attempt->id());
  }

  public function getExistingAttempt(QuizInterface $quiz, $user = NULL) {
    if ($user) {
      $account = $user;
    }
    else {
      $account = $this->currentUser;
    }

    // Get completed Attempts.
    $completedAttempts = $this->getCompletedAttempts($quiz, $account);
    $allowedAttempts = $quiz->get('field_allowed_attempt')->getString();

    // Allow further attempts only if attempts left.
    if (count($completedAttempts) < $allowedAttempts) {
      $currentAttempt = $this->getCurrentAttempt($quiz, $account);

      if (!empty($currentAttempt)) {
        return Attempt::load($currentAttempt);
      }
      else {
        return self::ATTEMPT_ALLOWED;
      }
    }
    else {
      return self::ATTEMPT_NOT_ALLOWED;
    }
  }

  public function getCompletedAttempts(QuizInterface $quiz, AccountInterface $account) {
    $query = \Drupal::entityQuery('attempt');
    $query->condition('status', 1);
    $query->condition('field_quiz', $quiz->id());
    $query->condition('field_user', $account->id());
    $query->condition('field_status', self::ATTEMPT_COMPLETED);
    $entity_ids = $query->execute();

    return $entity_ids;
  }

  public function getCurrentAttempt(QuizInterface $quiz, AccountInterface $account) {
    // Check for temp store first.
    $tempStoreAttempt = $this->tempStorePrivate->get('equiz:' . $quiz->id() . ':attempt');

    if ($tempStoreAttempt) {
      if (Attempt::load($tempStoreAttempt)) {
        $attempt_id = $tempStoreAttempt;
      }
      else {
        $attempt_id = FALSE;
      }
    }
    else {
      // Assuming only 1 progress attempt per user per quiz.
      $query = \Drupal::entityQuery('attempt');
      $query->condition('status', 1);
      $query->condition('field_quiz', $quiz->id());
      $query->condition('field_user', $account->id());
      $query->condition('field_status', [self::ATTEMPT_PROGRESS, self::ATTEMPT_STARTED], 'IN');
      $entity_id = $query->execute();

      $attempt_id = reset($entity_id);
      $this->tempStorePrivate->set('equiz:' . $quiz->id() . ':attempt', $attempt_id);
    }

    return $attempt_id;
  }

  public function initializeQuestions(QuizInterface $quiz, AttemptInterface $attempt) {
    // Fetch if already randomized.
    $quizQuestions = $this->tempStorePrivate->get('equiz:' . $quiz->id() . ':attempt:' . $attempt->id() . ':questions');

    if (empty($quizQuestions)) {
      $questions = $quiz->get('field_questions')->getValue();
      // Shuffle questions
      shuffle($questions);
      $this->tempStorePrivate->set('equiz:' . $quiz->id() . ':attempt:' . $attempt->id() . ':questions', $questions);
    }
  }

  public function fetchStepQuestion($step, AttemptInterface $attempt) {
    $quiz_id = $attempt->get('field_quiz')->getString();
    $quizQuestions = $this->tempStorePrivate->get('equiz:' . $quiz_id . ':attempt:' . $attempt->id() . ':questions');

    $question = NULL;
    if ($quizQuestions) {
      $question_id = $quizQuestions[$step - 1];
      $question = Question::load($question_id['target_id']);
    }
    else {
      // Need to recreate remaining random quiz questions list in temp private store.
//      $this->reinitializeQuiz(Quiz::load($quiz_id), $attempt);
    }

    return $question;
  }

  public function markStepQuestion($step, AttemptInterface $attempt, $mark = TRUE) {
    $quiz_id = $attempt->get('field_quiz')->getString();
    $markedQuestions = $this->getMarkedQuestions($attempt);

    if ($mark) {
      if (!in_array($step, $markedQuestions)) {
        $markedQuestions[] = $step;
        $this->tempStorePrivate->set('equiz:' . $quiz_id . ':attempt:' . $attempt->id() . ':questions:marked', $markedQuestions);
      }
    }
    else {
      if (in_array($step, $markedQuestions)) {
        $markedQuestions = array_diff($markedQuestions, [$step]);
        $this->tempStorePrivate->set('equiz:' . $quiz_id . ':attempt:' . $attempt->id() . ':questions:marked', $markedQuestions);
      }
    }

  }

  public function getMarkedQuestions(AttemptInterface $attempt, $step = NULL) {
    $quiz_id = $attempt->get('field_quiz')->getString();
    $markedQuestions = $this->tempStorePrivate->get('equiz:' . $quiz_id . ':attempt:' . $attempt->id() . ':questions:marked');
    $markedQuestions = !empty($markedQuestions) ? $markedQuestions : [];

    if ($step) {
      if (in_array($step, $markedQuestions)) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }

    return $markedQuestions;
  }

  public function buildQuestionOptions(QuestionInterface $question, AttemptInterface $attempt) {
    $quiz_id = $attempt->get('field_quiz')->getString();
    $quizQuestionOptions = $this->tempStorePrivate->get('equiz:' . $quiz_id . ':attempt:' . $attempt->id() . ':questions:' . $question->id());

    if (empty($quizQuestionOptions)) {
      $rawOptions = $question->get('field_option')->getValue();

      $options = [];
      foreach ($rawOptions as $key => $option) {
        $options['option_' . $key] = $option['value'];
      }

      $options = shuffle_assoc($options);
      $this->tempStorePrivate->set('equiz:' . $quiz_id . ':attempt:' . $attempt->id() . ':questions:' . $question->id(), $options);
      $quizQuestionOptions = $options;
    }

    return $quizQuestionOptions;
  }

  public function fetchStepUserAnswer($step, AttemptInterface $attempt) {
    $answer = '';
    $attemptedQuestions = $attempt->get('field_attempted_questions')->getValue();
    if (!empty($attemptedQuestions) && !empty($attemptedQuestions[$step - 1])) {
      $attemptedQuestion = Paragraph::load($attemptedQuestions[$step - 1]['target_id']);
      if (!empty($attemptedQuestion)) {
        $answer = $attemptedQuestion->get('field_user_answer')->getString();
      }
    }

    return $answer;
  }

  public function createNewQuestionAttempt($question, $user_input, $attempt) {
    if ($attempt instanceof Attempt) {
      $attempted_question = Paragraph::create(
        [
          'type' => 'attempted_question',
          'field_question' => $question->id(),
          'field_user_answer' => $user_input,
        ]
      );
      $attempted_question->save();

      if ($attempted_question->id()) {
        $existingAttempts = $attempt->get('field_attempted_questions')->getValue();
        $currentAttemptedQuestion = [
          'target_id' => $attempted_question->id(),
          'target_revision_id' => $attempted_question->getRevisionId(),
        ];

        if (!empty($existingAttempts)) {
          $attempt->field_attempted_questions[] = $currentAttemptedQuestion;
          $attempt->save();
        }
        else {
          $attempt->set('field_attempted_questions', $currentAttemptedQuestion);
          $attempt->save();
        }
      }
    }
  }

  public function updateExistingQuestionAttempt($step, $question, $user_input, $attempt, $attempted_questions) {
    // Try to Fetch corresponding step attempted_question.
    $current_attempt = !empty($attempted_questions[$step - 1]) ? $attempted_questions[$step - 1] : NULL;

    if (!empty($current_attempt)) {
      // Attempt Exists just resave the updated value.
      $current_attempt = Paragraph::load($current_attempt['target_id']);
      if ($current_attempt) {
        $current_attempt->set(            'field_question', $question->id());
        $current_attempt->set(            'field_user_answer', $user_input);
        $current_attempt->save();
      }
    }
    else {
      // Need to create the attempt.
      $this->createNewQuestionAttempt($question, $user_input, $attempt);
    }
  }

  public function buildProgressMarkup(AttemptInterface $attempt, $currentStep) {
    $attemptedQuestions = $attempt->get('field_attempted_questions')->getValue();
    $quiz_id = $attempt->get('field_quiz')->getString();

    $stepLinks = [];
    if ($currentStep !== 'review') {
      if (!empty($attemptedQuestions)) {
        foreach ($attemptedQuestions as $delta => $attemptedQuestion) {
          $doubtful = $this->getMarkedQuestions($attempt, $delta + 1);
          $doubtful = ($doubtful) ? ' *' : '';
          $classes = '';
          $classes .= ($doubtful) ? 'marked ' : '';

          // Add skipped class for skipped questions.
          $attemptedQuestion = Paragraph::load($attemptedQuestion['target_id']);
          if (($attemptedQuestion instanceof Paragraph)) {
            $userInput = $attemptedQuestion->get('field_user_answer')->getValue();
            if (isset($userInput[0]) && $userInput[0] !== NULL) {
              $classes .= 'answered ';
            }
            else {
              $classes .= 'skipped ';
            }
          }

          // Compose Steps.
          if ($delta == ($currentStep - 1)) {
            $stepLinks[] = t('Q @num', ['@num' => $currentStep])->__toString() . $doubtful;
          }
          else {
            $stepLinks[] = Link::createFromRoute(
              t('Q @num',  ['@num' => $delta + 1]) . $doubtful,
              'equiz.equiz_attempt_controller_process_question',
              [
                'quiz' => $quiz_id,
                'step' => $delta + 1,
              ],
              [
                'attributes' => [
                  'class' => $classes,
                ]
              ]
            )->toString()->__toString();
          }
        }

        if (count($stepLinks) < $currentStep) {
          $stepLinks[] = t('Q @num', ['@num' => $currentStep])->__toString();
        }

        $quiz = Quiz::load($quiz_id);
        $total_questions  = $quiz->get('field_question_count')->getString();
        if (count($stepLinks) === (int) $total_questions) {
          // Add review page link as well.
          $stepLinks[] =  Link::createFromRoute(
            t('Review'),
            'equiz.equiz_attempt_controller_review',
            [
              'quiz' => $quiz_id,
            ]
          )->toString()->__toString();
        }
      }
    }
    else {
      if (!empty($attemptedQuestions)) {
        foreach ($attemptedQuestions as $delta => $attemptedQuestion) {
          $stepLinks[] = Link::createFromRoute(
            t('Q @num',  ['@num' => $delta + 1]),
            'equiz.equiz_attempt_controller_process_question',
            [
              'quiz' => $quiz_id,
              'step' => $delta + 1,
            ]
          )->toString()->__toString();
        }
      }
    }

    return ($stepLinks) ? join(' > ', $stepLinks) : '';
  }

  public function reviewAttemptStatus(AttemptInterface $attempt) {
    $status = [
      'total_questions' => '',
      'total_answered' => [],
      'total_skipped' => [],
      'total_marked' => [],
    ];

    $quiz = $attempt->get('field_quiz')->getString();
    $quiz = Quiz::load($quiz);
    $total_questions  = $quiz->get('field_question_count')->getString();
    $status['total_questions'] = $total_questions;
    $status['total_marked'] = $this->getMarkedQuestions($attempt);

    $attemptedQuestions = $attempt->get('field_attempted_questions')->getValue();
    if (!empty($attemptedQuestions)) {
      foreach ($attemptedQuestions as $delta => $attemptedQuestion) {
        $attemptedQuestion = Paragraph::load($attemptedQuestion['target_id']);
        if (($attemptedQuestion instanceof Paragraph)) {
          $userInput = $attemptedQuestion->get('field_user_answer')->getValue();
          if (isset($userInput[0]) && $userInput[0] !== NULL) {
            $status['total_answered'][] = $delta + 1;
          }
          else {
            $status['total_skipped'][] = $delta + 1;
          }
        }
      }
    }
    else {
      $status = FALSE;
    }

    return $status;
  }

  public function prepareQuizAttemptResult(AttemptInterface $attempt) {
    // Create result entity.
    $quiz = $attempt->get('field_quiz')->getString();
    $user = $attempt->get('field_user')->getString();
    $result = FALSE;
    if ($quiz && $user) {
      try {
        $result = Result::create(
          [
            'status' => 1,
            'field_quiz' => $quiz,
            'field_user' => $user,
            'field_attempt' => $attempt->id(),
            'name' => User::load($user)->label(),
            'field_time_taken' => $this->timeManager->timeSpent($attempt),
          ]
        );
        $result->save();

        $this->calculateResult($result);
      }
      catch (\Exception $exception) {
        watchdog_exception('equiz_result', $exception);
      }
    }

    return $result;
  }

  protected function calculateResult(ResultInterface $result) {
    $attempt = $result->get('field_attempt')->getString();
    $attempt = Attempt::load($attempt);

    // Compute field_score and field_soft_score.
    if ($attempt instanceof AttemptInterface) {
      $quiz = $attempt->get('field_quiz')->getString();
      $quiz = Quiz::load($quiz);

      $total_questions = $quiz->get('field_question_count')->getString();

      // Parse attempted questions.
      $attemptedQuestions = $attempt->get('field_attempted_questions')->getValue();
      $attemptStatus = $this->reviewAttemptStatus($attempt);

      // For attemptedQuestion Calculate positive score
      if (!empty($attemptStatus) && !empty($attemptStatus['total_answered'])) {
        $score = $this->calculateResultScore($attemptStatus['total_answered'], $attemptedQuestions, $quiz);
        $result->set('field_score', $score['field_score']);
        $result->set('field_soft_score', $score['field_soft_score']);
        $name = $result->get('name')->getString();
        $result->set('name', $name . ' - Quiz: ' . $quiz->label());
        $result->save();
      }
    }
  }

  protected function calculateResultScore($answeredQuestions, $attemptedQuestions, QuizInterface $quiz) {
    $score = [
      'field_score' => 0,
      'field_soft_score' => 0,
    ];
    $positive_score = $quiz->get('field_positive_score')->getString();
    $negative_score = $quiz->get('field_negative_score')->getString();

    foreach ($answeredQuestions as $answeredQuestion) {
      $actualQuestionAttempt = $attemptedQuestions[$answeredQuestion - 1];
      $actualQuestionAttempt = Paragraph::load($actualQuestionAttempt['target_id']);

      if ($actualQuestionAttempt) {
        $originalQuestion = $actualQuestionAttempt->get('field_question')->getString();
        $originalQuestion = Question::load($originalQuestion);
        $correctAnswer = (int) $originalQuestion->get('field_correct_option')->getString();
        $questionWeight = (int) $originalQuestion->get('field_weight')->getString();
        $userAnswer = (int) $actualQuestionAttempt->get('field_user_answer')->getString();

        if ($correctAnswer === $userAnswer) {
          // Give positive score.
          $score['field_score'] += $positive_score;
          $score['field_soft_score'] += $questionWeight * $positive_score;
        }
        else {
          $score['field_score'] -= $negative_score;
          $score['field_soft_score'] -= $questionWeight * $negative_score;
        }
      }
    }

    return $score;
  }

  public function performCleanup(AttemptInterface $attempt) {
    $quiz_id = $attempt->get('field_quiz')->getString();
    $quiz = Quiz::load($quiz_id);
    $tempStoreIds = [];
    $attempt_id = $attempt->id();

    $tempStoreIds[] = 'equiz:' . $quiz_id . ':attempt';
    $tempStoreIds[] = 'equiz:' . $quiz_id . ':attempt:' . $attempt_id . ':questions';

    $questions = $quiz->get('field_questions')->getValue();

    foreach ($questions as $question) {
      $tempStoreIds[] = 'equiz:' . $quiz_id . ':attempt:' . $attempt_id . ':questions:' . $question['target_id'];
    }

    try {
      foreach ($tempStoreIds as $tempStoreId) {
        $this->tempStorePrivate->delete($tempStoreId);
      }
    }
    catch (\Exception $exception) {
      watchdog_exception('equiz_attempt', $exception);
    }
  }

  public function getAttemptResult(QuizInterface $quiz, AttemptInterface $attempt, AccountInterface $account) {
    $query = \Drupal::entityQuery('result');
    $query->condition('status', 1);
    $query->condition('field_quiz', $quiz->id());
    $query->condition('field_attempt', $attempt->id());
    $query->condition('field_user', $account->id());
    $entity_id = $query->execute();

    $result_id = reset($entity_id);
    return $result_id;
  }

  public function verifyTimer(AttemptInterface $attempt) {
    $allowed = TRUE;

    $this->timeManager->verifyStartEndTime($attempt);

    // Check if endtime has reached.
    if ($this->timeManager->reachedEndTime($attempt)) {
      $allowed = FALSE;
      // Submit the attempt as well.
      $attempt->set('field_status', AttemptManager::ATTEMPT_COMPLETED);
      $attempt->save();
      $result = $this->prepareQuizAttemptResult($attempt);
      if ($result instanceof Result) {
        $this->performCleanup($attempt);
      }
    }

    return $allowed;
  }

  public function considerWastedTime(AttemptInterface $attempt, $timeWasted) {
    // Allow maximum of 2 seconds increase in time limit per request.
    $timeWasted = (int) $timeWasted * 2;
    $timeWasted = ($timeWasted > 2000) ? 2000 : $timeWasted;
    $this->timeManager->updateEndTime($attempt, $timeWasted);
  }

//  public function reinitializeQuiz(QuizInterface $quiz, AttemptInterface $attempt) {
//    $questions = $quiz->get('field_questions')->getValue();
//    $attemptedQuestions = $attempt->get('field_attempted_questions')->getValue();
//
//    // Exempt attempted questions from questions list and prepare new random questions list.
//    $quizQuestions = [];
//    foreach ($attemptedQuestions as $key => $attemptedQuestion) {
//      $attemptedQuestionPara = Paragraph::load($attemptedQuestion['target_id']);
//      $question_id = $attemptedQuestionPara->get('field_question')->getString();
//      $quizQuestions[] = ['target_id' => $question_id];
//    }
//  }

}
