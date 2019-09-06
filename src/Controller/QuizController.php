<?php

namespace Drupal\equiz\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\equiz\Entity\QuizInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class QuizController.
 *
 *  Returns responses for Quiz routes.
 */
class QuizController extends ControllerBase implements ContainerInjectionInterface {


  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new QuizController.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   */
  public function __construct(DateFormatter $date_formatter, Renderer $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays a Quiz revision.
   *
   * @param int $quiz_revision
   *   The Quiz revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($quiz_revision) {
    $quiz = $this->entityTypeManager()->getStorage('quiz')
      ->loadRevision($quiz_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('quiz');

    return $view_builder->view($quiz);
  }

  /**
   * Page title callback for a Quiz revision.
   *
   * @param int $quiz_revision
   *   The Quiz revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($quiz_revision) {
    $quiz = $this->entityTypeManager()->getStorage('quiz')
      ->loadRevision($quiz_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $quiz->label(),
      '%date' => $this->dateFormatter->format($quiz->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Quiz.
   *
   * @param \Drupal\equiz\Entity\QuizInterface $quiz
   *   A Quiz object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(QuizInterface $quiz) {
    $account = $this->currentUser();
    $quiz_storage = $this->entityTypeManager()->getStorage('quiz');

    $langcode = $quiz->language()->getId();
    $langname = $quiz->language()->getName();
    $languages = $quiz->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $quiz->label()]) : $this->t('Revisions for %title', ['%title' => $quiz->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all quiz revisions") || $account->hasPermission('administer quiz entities')));
    $delete_permission = (($account->hasPermission("delete all quiz revisions") || $account->hasPermission('administer quiz entities')));

    $rows = [];

    $vids = $quiz_storage->revisionIds($quiz);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\equiz\QuizInterface $revision */
      $revision = $quiz_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $quiz->getRevisionId()) {
          $link = $this->l($date, new Url('entity.quiz.revision', [
            'quiz' => $quiz->id(),
            'quiz_revision' => $vid,
          ]));
        }
        else {
          $link = $quiz->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.quiz.translation_revert', [
                'quiz' => $quiz->id(),
                'quiz_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.quiz.revision_revert', [
                'quiz' => $quiz->id(),
                'quiz_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.quiz.revision_delete', [
                'quiz' => $quiz->id(),
                'quiz_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['quiz_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
