<?php

namespace Drupal\duplicate_node\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Module settings form.
 */
class DuplicateNodeSettingsForm extends DuplicateNodeEntitySettingsForm {

  /**
   * The machine name of the entity type.
   *
   * @var string
   *   The entity type id i.e. node
   */
  protected $entityTypeId = 'node';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'duplicate_node_node_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['prefix_for_node_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix For Node Title'),
      '#default_value' => $this->getSettings('prefix_for_node_title'),
      '#description' => $this->t('Enter text to add to the title of a duplicated node to help content editors. A space will be added between this text and the title. Example: "Duplicate of"'),
    ];

    $form['duplicate_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Duplicate publication status of original?'),
      '#default_value' => $this->getSettings('duplicate_status'),
      '#description' => $this->t('If unchecked, the publication status of the duplicate will be equal to the default of the content type.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $form_values = $form_state->getValues();
    $this->config('duplicate_node.settings')->set('prefix_for_node_title', $form_values['prefix_for_node_title'])->save();
    $this->config('duplicate_node.settings')->set('duplicate_status', $form_values['duplicate_status'])->save();

    parent::submitForm($form, $form_state);
  }

}
