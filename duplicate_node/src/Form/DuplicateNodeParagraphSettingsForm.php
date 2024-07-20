<?php

namespace Drupal\duplicate_node\Form;

/**
 * Module settings form.
 */
class DuplicateNodeParagraphSettingsForm extends DuplicateNodeEntitySettingsForm {

  /**
   * The machine name of the entity type.
   *
   * @var string
   *   The entity type id i.e. node
   */
  protected $entityTypeId = 'paragraph';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'duplicate_node_paragraph_setting_form';
  }

}
