<?php

namespace Drupal\equiz\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Question entities.
 */
class QuestionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
