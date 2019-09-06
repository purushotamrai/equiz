<?php

namespace Drupal\equiz\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\equiz\Entity\QuestionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class QuestionController.
 *
 *  Returns responses for Question routes.
 */
class QuestionController extends ControllerBase implements ContainerInjectionInterface {


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
   * Constructs a new QuestionController.
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
   * Displays a Question revision.
   *
   * @param int $question_revision
   *   The Question revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($question_revision) {
    $question = $this->entityTypeManager()->getStorage('question')
      ->loadRevision($question_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('question');

    return $view_builder->view($question);
  }

  /**
   * Page title callback for a Question revision.
   *
   * @param int $question_revision
   *   The Question revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($question_revision) {
    $question = $this->entityTypeManager()->getStorage('question')
      ->loadRevision($question_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $question->label(),
      '%date' => $this->dateFormatter->format($question->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Question.
   *
   * @param \Drupal\equiz\Entity\QuestionInterface $question
   *   A Question object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(QuestionInterface $question) {
    $account = $this->currentUser();
    $question_storage = $this->entityTypeManager()->getStorage('question');

    $langcode = $question->language()->getId();
    $langname = $question->language()->getName();
    $languages = $question->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $question->label()]) : $this->t('Revisions for %title', ['%title' => $question->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all question revisions") || $account->hasPermission('administer question entities')));
    $delete_permission = (($account->hasPermission("delete all question revisions") || $account->hasPermission('administer question entities')));

    $rows = [];

    $vids = $question_storage->revisionIds($question);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\equiz\QuestionInterface $revision */
      $revision = $question_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $question->getRevisionId()) {
          $link = $this->l($date, new Url('entity.question.revision', [
            'question' => $question->id(),
            'question_revision' => $vid,
          ]));
        }
        else {
          $link = $question->link($date);
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
              Url::fromRoute('entity.question.translation_revert', [
                'question' => $question->id(),
                'question_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.question.revision_revert', [
                'question' => $question->id(),
                'question_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.question.revision_delete', [
                'question' => $question->id(),
                'question_revision' => $vid,
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

    $build['question_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
