<?php

namespace Drupal\duplicate_node\Controller;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\duplicate_node\Entity\DuplicateNodeEntityFormBuilder;
use Drupal\node\Controller\NodeController;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Duplicate Node routes.
 */
class DuplicateNodeController extends NodeController {

  /**
   * The entity form builder.
   *
   * @var \Drupal\duplicate_node\Form\DuplicateNodeEntityFormBuilder
   */
  protected $qncEntityFormBuilder;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\duplicate_node\Entity\DuplicateNodeEntityFormBuilder $entity_form_builder
   *   The entity form builder.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, EntityRepositoryInterface $entity_repository, DuplicateNodeEntityFormBuilder $entity_form_builder) {
    parent::__construct($date_formatter, $renderer, $entity_repository);
    $this->qncEntityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('entity.repository'),
      $container->get('duplicate_node.entity.form_builder')
    );
  }

  /**
   * Retrieves the entity form builder.
   *
   * @return \Drupal\duplicate_node\Form\QuickNodeDuplicateFormBuilder
   *   The entity form builder.
   */
  protected function entityFormBuilder() {
    return $this->qncEntityFormBuilder;
  }

  /**
   * Provides the node submission form.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity to duplicate.
   *
   * @return array
   *   A node submission form.
   */
  public function duplicateNode(Node $node) {
    if (!empty($node)) {
      $form = $this->entityFormBuilder()->getForm($node, 'duplicate_node');
      return $form;
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * The _title_callback for the node.add route.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function duplicatePageTitle(Node $node) {
    $prepend_text = "";
    $config = \Drupal::config('duplicate_node.settings');
    if (!empty($config->get('prefix_for_node_title'))) {
      $prepend_text = $config->get('prefix_for_node_title') . " ";
    }
    return $prepend_text . $node->getTitle();
  }

}
