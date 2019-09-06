<?php

namespace Drupal\equiz;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\equiz\Entity\AttemptInterface;
use Drupal\equiz\Entity\QuestionInterface;
use Drupal\equiz\Entity\QuizInterface;

/**
 * Interface AttemptManagerInterface.
 */
interface AttemptManagerInterface {

  /**
   * @param QuizInterface $quiz
   * @param null $currentUser
   * @param bool $attemptQuiz
   * @return mixed
   */
  public function userAttemptAllowed(QuizInterface $quiz, $currentUser = NULL, $attemptQuiz = TRUE);

  /**
   * @param QuizInterface $quiz
   * @param AccountInterface $account
   * @return mixed
   */
  public function getCompletedAttempts(QuizInterface $quiz, AccountInterface $account);

  /**
   * @param QuizInterface $quiz
   * @param AccountInterface $account
   * @return mixed
   */
  public function getCurrentAttempt(QuizInterface $quiz, AccountInterface $account);

  /**
   * @param QuizInterface $quiz
   * @param null $user
   * @return mixed
   */
  public function getExistingAttempt(QuizInterface $quiz, $user = NULL);

  /**
   * @param QuizInterface $quiz
   * @param AttemptInterface $attempt
   * @return mixed
   */
  public function initializeQuestions(QuizInterface $quiz, AttemptInterface $attempt);

  /**
   * @param int $step
   * @param AttemptInterface $attempt
   * @return mixed
   */
  public function fetchStepQuestion($step, AttemptInterface $attempt);

  /**
   * @param $step
   * @param AttemptInterface $attempt
   * @return mixed
   */
  public function markStepQuestion($step, AttemptInterface $attempt, $mark = TRUE);

  /**
   * @param AttemptInterface $attempt
   * @param null $step
   * @return mixed
   */
  public function getMarkedQuestions(AttemptInterface $attempt, $step = NULL);

  /**
   * @param QuestionInterface $question
   * @param AttemptInterface $attempt
   * @return mixed
   */
  public function buildQuestionOptions(QuestionInterface $question, AttemptInterface $attempt);

  /**
   * @param $step
   * @param AttemptInterface $attempt
   * @return mixed
   */
  public function fetchStepUserAnswer($step, AttemptInterface $attempt);

  /**
   * @param $question
   * @param $user_input
   * @param $attempt
   * @return mixed
   */
  public function createNewQuestionAttempt($question, $user_input, $attempt);

  /**
   * @param $step
   * @param $question
   * @param $user_input
   * @param $attempt
   * @param $attemptedQuestions
   * @return mixed
   */
  public function updateExistingQuestionAttempt($step, $question, $user_input, $attempt, $attemptedQuestions);

  /**
   * @param AttemptInterface $attempt
   * @param $currentStep
   * @return mixed
   */
  public function buildProgressMarkup(AttemptInterface $attempt, $currentStep);

    /**
   * @param AttemptInterface $attempt
   * @return mixed
   */
  public function reviewAttemptStatus(AttemptInterface $attempt);

  /**
   * @param AttemptInterface $attempt
   * @return mixed
   */
  public function prepareQuizAttemptResult(AttemptInterface $attempt);

  /**
   * @param AttemptInterface $attempt
   * @return mixed
   */
  public function performCleanup(AttemptInterface $attempt);

  /**
   * @param QuizInterface $quiz
   * @param AttemptInterface $attempt
   * @param AccountInterface $account
   * @return mixed
   */
  public function getAttemptResult(QuizInterface $quiz, AttemptInterface $attempt, AccountInterface $account);

  /**
   * @param AttemptInterface $attempt
   * @return mixed
   */
  public function verifyTimer(AttemptInterface $attempt);

  /**
   * @param AttemptInterface $attempt
   * @param $timeWasted
   * @return mixed
   */
  public function considerWastedTime(AttemptInterface $attempt, $timeWasted);
}
