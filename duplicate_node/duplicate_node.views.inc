<?php

/**
 * @file
 * Provide views data for duplicate_node.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function duplicate_node_views_data_alter(&$data) {
  $data['node']['duplicate_link'] = [
    'field' => [
      'title' => t('Add duplicate link'),
      'help' => t('Provide a duplicate link to the Content.'),
      'id' => 'duplicate_node_link',
    ],
  ];
}
