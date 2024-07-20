<?php

namespace Drupal\duplicate_node\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * Access control for duplicating nodes.
 */
class DuplicateNodeAccess {

  /**
   * Limit access to the duplicate according to their restricted state.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object.
   * @param int $node
   *   The node id.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   If allowed, AccessResultAllowed isAllowed() will be TRUE. If forbidden,
   *   isForbidden() will be TRUE.
   */
  public function duplicateNode(AccountInterface $account, $node) {
    $node = Node::load($node);

    if (_duplicate_node_has_duplicate_permission($node)) {
      $result = AccessResult::allowed();
    }
    else {
      $result = AccessResult::forbidden();
    }

    $result->addCacheableDependency($node);

    return $result;
  }

}
