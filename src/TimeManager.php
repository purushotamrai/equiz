<?php

namespace Drupal\equiz;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\equiz\Entity\AttemptInterface;
use DateTime;
use DateTimeZone;
use Drupal\equiz\Entity\Quiz;

/**
 * Class TimeManager.
 */
class TimeManager implements TimeManagerInterface {

  /**
   * Drupal\Core\TempStore\PrivateTempStoreFactory definition.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempstorePrivate;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TimeManager object.
   */
  public function __construct(PrivateTempStoreFactory $tempstore_private, AccountProxyInterface $current_user, EntityTypeManagerInterface $entityTypeManager) {
    $this->tempstorePrivate = $tempstore_private;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entityTypeManager;
  }

  public function verifyStartEndTime(AttemptInterface $attempt) {
    $exists = TRUE;
    // Check if start and end time exists.
    $startTime = $attempt->get('field_start_time')->getString();
    $endTime = $attempt->get('field_end_time')->getString();
    $quiz = $attempt->get('field_quiz')->getString();
    $quiz = $this->entityTypeManager->getStorage('quiz')->load($quiz);
    $timeZone = new DateTimeZone('UTC');

    $quizTimeLimit = FALSE;
    if ($quiz instanceof Quiz) {
      $quizTimeLimit = $quiz->get('field_time_limit')->getString();
    }

    if (!empty($quizTimeLimit)) {
      if (empty($startTime)) {
        // Initialize both start and end time.
        $startTime = new DateTime('now', $timeZone);
        $attempt->set('field_start_time', $startTime->format("Y-m-d\TH:i:s"));
        $startTimeTimestamp = $startTime->getTimestamp();

        $endTime = new DateTime('now', $timeZone);
        $endTime->setTimestamp($startTimeTimestamp + (int)$quizTimeLimit + 1);
        $attempt->set('field_end_time', $endTime->format("Y-m-d\TH:i:s"));
        $attempt->save();
      } elseif (empty($endTime)) {
        // Initialize just anticipated end time.
        $startTime = new DateTime($startTime, $timeZone);
        $startTimeTimestamp = $startTime->getTimestamp();
        $endTime = new DateTime('now', $timeZone);
        $endTime->setTimestamp($startTimeTimestamp + (int)$quizTimeLimit + 1);
        $attempt->set('field_end_time', $endTime->format("Y-m-d\TH:i:s"));
        $attempt->save();
      }
    }
    return $exists;
  }

  public function reachedEndTime(AttemptInterface $attempt) {
    $completed = FALSE;

    $endTime = $attempt->get('field_end_time')->getString();
    $timeZone = new DateTimeZone('UTC');
    $currentTime = new DateTime('now', $timeZone);
    $currentTime = $currentTime->getTimestamp();
    $endTime = new DateTime($endTime, $timeZone);
    $endTime = $endTime->getTimestamp();

    if ($currentTime >= $endTime) {
      $completed = TRUE;
    }

    return $completed;
  }

  public function updateEndTime(AttemptInterface $attempt, $timeWasted) {
    if (ceil($timeWasted/1000)) {
      $endTime = $attempt->get('field_end_time')->getString();
      $timeZone = new DateTimeZone('UTC');
      $endTime = new DateTime($endTime, $timeZone);
      $endTime = $endTime->getTimestamp();
      $endTime = $endTime + ceil($timeWasted/1000);

      $newEndTime = new DateTime('now', $timeZone);
      $newEndTime->setTimestamp($endTime);
      $attempt->set('field_end_time', $newEndTime->format("Y-m-d\TH:i:s"));
      $attempt->save();
    }
  }

  public function timeSpent(AttemptInterface $attempt) {
    $timeZone = new DateTimeZone('UTC');

    $endTime = $attempt->get('field_end_time')->getString();
    $endTime = new DateTime($endTime, $timeZone);
    $endTime = $endTime->getTimestamp();

    $quiz = $attempt->get('field_quiz')->getString();
    $quiz = $this->entityTypeManager->getStorage('quiz')->load($quiz);
    $quizTimeLimit = FALSE;
    if ($quiz instanceof Quiz) {
      $quizTimeLimit = (int) $quiz->get('field_time_limit')->getString();
    }

    $timeSpent = new DateTime('now', $timeZone);
    $timeSpent = $timeSpent->getTimestamp();
    $timeSpent = $timeSpent + $quizTimeLimit - $endTime;

    return $timeSpent;
  }
}
