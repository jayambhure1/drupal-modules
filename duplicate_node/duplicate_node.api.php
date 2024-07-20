<?php

/**
 * @file
 * API documentation.
 */

use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * @file
 * Hooks related to duplicate_node module and it's plugins.
 */

/**
 * Called when a node is Duplicated.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node being Duplicated.
 */
function hook_Duplicated_node_alter(NodeInterface &$node) {
  $node->setTitle('Old node Duplicated');
  $node->save();
}

/**
 * Called when a node is duplicated with a paragraph field.
 *
 * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
 *   The paragraph entity.
 * @param string $pfield_name
 *   The paragraph field name.
 * @param mixed $pfield_settings
 *   The paragraph settings.
 */
function hook_duplicated_node_paragraph_field_alter(Paragraph &$paragraph, $pfield_name, $pfield_settings) {

}
