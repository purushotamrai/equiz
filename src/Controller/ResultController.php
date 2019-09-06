<?php

namespace Drupal\equiz\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\equiz\Entity\ResultInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ResultController.
 *
 *  Returns responses for Result routes.
 */
class ResultController extends ControllerBase implements ContainerInjectionInterface {


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
   * Constructs a new ResultController.
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
   * Displays a Result revision.
   *
   * @param int $result_revision
   *   The Result revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($result_revision) {
    $result = $this->entityTypeManager()->getStorage('result')
      ->loadRevision($result_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('result');

    return $view_builder->view($result);
  }

  /**
   * Page title callback for a Result revision.
   *
   * @param int $result_revision
   *   The Result revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($result_revision) {
    $result = $this->entityTypeManager()->getStorage('result')
      ->loadRevision($result_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $result->label(),
      '%date' => $this->dateFormatter->format($result->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Result.
   *
   * @param \Drupal\equiz\Entity\ResultInterface $result
   *   A Result object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ResultInterface $result) {
    $account = $this->currentUser();
    $result_storage = $this->entityTypeManager()->getStorage('result');

    $build['#title'] = $this->t('Revisions for %title', ['%title' => $result->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all result revisions") || $account->hasPermission('administer result entities')));
    $delete_permission = (($account->hasPermission("delete all result revisions") || $account->hasPermission('administer result entities')));

    $rows = [];

    $vids = $result_storage->revisionIds($result);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\equiz\ResultInterface $revision */
      $revision = $result_storage->loadRevision($vid);
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $result->getRevisionId()) {
          $link = $this->l($date, new Url('entity.result.revision', [
            'result' => $result->id(),
            'result_revision' => $vid,
          ]));
        }
        else {
          $link = $result->link($date);
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
              'url' => Url::fromRoute('entity.result.revision_revert', [
                'result' => $result->id(),
                'result_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.result.revision_delete', [
                'result' => $result->id(),
                'result_revision' => $vid,
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

    $build['result_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
