<?php

namespace Drupal\duplicate_node;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Module permissions.
 */
class DuplicateNodePermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of permissions.
   *
   * @return array
   *   The permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function duplicateTypePermissions() {
    $perms = [];
    // Generate node permissions for all node types.
    foreach (NodeType::loadMultiple() as $type) {
      $type_id = $type->id();
      $type_params = ['%type' => $type->label()];
      $perms += [
        "duplicate $type_id content" => [
          'title' => $this->t('%type: duplicate content', $type_params),
        ],
      ];
    }
    return $perms;
  }

}
