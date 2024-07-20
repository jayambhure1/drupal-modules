<?php

namespace Drupal\duplicate_node\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Handler for showing Duplicate Node link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("duplicate_node_link")
 */
class DuplicateLink extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = ['default' => $this->getDefaultLabel()];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to display'),
      '#default_value' => $this->options['text'],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * Returns the default label for the link.
   *
   * @return string
   *   The default link label.
   */
  protected function getDefaultLabel() {
    return $this->t('Duplicate');
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $node = $this->getEntity($values);

    if (!$node) {
      return '';
    }

    $url = Url::fromRoute('duplicate_node.node.duplicate_node', [
      'node' => $node->id(),
    ]);

    if (!$url->access()) {
      return '';
    }

    return [
      '#type' => 'link',
      '#url' => $url,
      '#title' => $this->options['text'] ?: $this->getDefaultLabel(),
    ];
  }

}
