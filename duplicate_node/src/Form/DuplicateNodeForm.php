<?php

namespace Drupal\duplicate_node\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeForm;

/**
 * Form controller for Duplicate Node edit forms.
 *
 * We can override most of the node form from here! Hooray!
 */
class DuplicateNodeForm extends NodeForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    // Brand the Publish / Unpublish buttons, but first check if they are still
    // there.
    $duplicate_string = $this->t('New Duplicate');
    if (!empty($element['publish']['#value'])) {
      $element['publish']['#value'] = $duplicate_string . ' ' . $element['publish']['#value'];
    }
    if (!empty($element['unpublish']['#value'])) {
      $element['unpublish']['#value'] = $duplicate_string . ' ' . $element['unpublish']['#value'];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    // Duplicate block start.
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('layout_builder')) {
      $config = \Drupal::config("duplicate_node.settings");
      if (!empty($config->get('prefix_for_node_title'))) {
        $prefix_for_node_title = $config->get('prefix_for_node_title') . " ";
      }
      else {
        $prefix_for_node_title = "Duplicate of ";
      }

      $layout_builder_data = $this->entity->get('layout_builder__layout')->getValue();
      if ($layout_builder_data) {
        foreach ($layout_builder_data as $section) {
          if ($section) {
            $section = reset($section);
            foreach ($section->getComponents() as $component) {
              $block_plugin_id = $component->getPluginId();
              if (strpos($block_plugin_id, "block_content") !== FALSE) {
                $block_uuid = str_replace("block_content:", '', $block_plugin_id);
                $block = \Drupal::service("entity.repository")->loadEntityByUuid('block_content', $block_uuid);
                $new_block_label = $prefix_for_node_title . $block->label();
                $block->set('info', $new_block_label);
                $block_entity_type = $block->getEntityType();
                $block->{$block_entity_type->getKey('id')} = NULL;
                $block->enforceIsNew();

                $new_uuid = \Drupal::service('uuid')->generate();

                if ($block_entity_type->hasKey('uuid')) {
                  $block->{$block_entity_type->getKey('uuid')} = $new_uuid;
                }
                $block->save();

                $block_configuration = $component->get('configuration');
                $new_plugin_id = 'block_content:' . $new_uuid;
                $block_configuration['label'] = $new_block_label;
                $block_configuration['id'] = $new_plugin_id;
                $component->setConfiguration($block_configuration);
              }
            }

          }
        }
      }

    }

    // Duplicate block end.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entity;
    $insert = $node->isNew();
    $node->save();
    $node_link = $node->toLink($this->t('View'))->toString();
    $context = [
      '@type' => $node->getType(),
      '%title' => $node->label(),
      'link' => $node_link,
    ];
    $t_args = [
      '@type' => node_get_type_label($node),
      '%title' => $node->label(),
    ];

    if ($insert) {
      $this->logger('content')
        ->notice('@type: added %title (duplicate).', $context);
      $this->messenger()->addMessage($this->t('@type %title (duplicate) has been created.', $t_args));
    }

    if ($node->id()) {
      $form_state->setValue('nid', $node->id());
      $form_state->set('nid', $node->id());
      $storage = $form_state->getStorage();
      foreach ($storage['duplicate_node_groups_storage'] as $group) {
        // Add node to all the groups the original was in
        // (if group and gnode modules aren't installed then nothing should ever
        // be set in this array anyway)
        $add_method = method_exists($group, 'addContent') ? 'addContent' : 'addRelationship';
        $group->{$add_method}($node, 'group_node:' . $node->bundle());
      }
      if ($node->access('view')) {
        $form_state->setRedirect(
          'entity.node.canonical',
          ['node' => $node->id()]
        );
      }
      else {
        $form_state->setRedirect('<front>');
      }

    }
    else {
      // In the unlikely case something went wrong on save, the node will be
      // rebuilt and node form redisplayed the same way as in preview.
      $this->messenger()->addError($this->t('The duplicated post could not be saved.'));
      $form_state->setRebuild();
    }
  }

}
