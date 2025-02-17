<?php

namespace Drupal\duplicate_node\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class to configure how entities are duplicated.
 *
 * @todo write the interface.
 */
abstract class DuplicateNodeEntitySettingsForm extends ConfigFormBase implements DuplicateNodeEntitySettingsFormInterface {

  /**
   * The Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Entity Bundle Type Info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The machine name of the entity type.
   *
   * @var string
   *   The entity type i.e. node
   */
  protected $entityTypeId = '';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityType($entityTypeId) {
    $this->entityTypeId = $entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['duplicate_node.settings'];
  }

  /**
   * DuplicateNodeEntitySettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info provider.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityFieldManagerInterface $entityFieldManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    parent::__construct($configFactory);
    $this->configFactory = $configFactory;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['exclude'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Exclusion list'),
    ];
    $form['exclude']['description'] = [
      '#markup' => $this->t('You can select fields that you do not want to be included when the node is duplicated.'),
    ];

    $config_name = 'exclude.' . $this->getEntityTypeId();
    if (!is_null($this->getSettings($config_name))) {
      $value = $this->getSettings($config_name);
      if (empty($form_state->getValue('bundle_names'))) {
        $form_state->setValue('bundle_names', $value);
      }
    }

    $bundle_names = [];
    foreach ($this->getEntityBundles() as $bundle => $item) {
      $bundle_names[$bundle] = $item['label'];
    }
    $form['exclude']['bundle_names'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entity Types'),
      '#options' => $bundle_names,
      '#default_value' => array_keys($form_state->getValue('bundle_names') ?: []),
      '#description' => $this->t('Select entity types above and you will see a list of fields that can be excluded.'),
      '#ajax' => [
        'callback' => 'Drupal\duplicate_node\Form\DuplicateNodeEntitySettingsForm::fieldsCallback',
        'wrapper' => 'fields-list-' . $this->getEntityTypeId(),
        'method' => 'replace',
      ],
    ];

    $form['exclude']['fields'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Fields'),
      '#description' => $this->getDescription($form_state),
      '#prefix' => '<div id="fields-list-' . $this->getEntityTypeId() . '">',
      '#suffix' => '</div>',
    ];

    if ($selected_bundles = $this->getSelectedBundles($form_state)) {
      $selected_bundles = $this->getSelectedBundles($form_state);
      foreach ($bundle_names as $bundle_name => $bundle_label) {
        if (!empty($selected_bundles[$bundle_name])) {
          $options = [];
          $field_definitions = $this->entityFieldManager->getFieldDefinitions($this->getEntityTypeId(), $bundle_name);
          foreach ($field_definitions as $field) {
            if ($field instanceof FieldConfig) {
              $options[$field->getName()] = $field->getLabel();
            }
          }
          $form['exclude']['fields']['bundle_' . $bundle_name] = [
            '#type' => 'details',
            '#title' => $bundle_name,
            '#open' => TRUE,
          ];
          $form['exclude']['fields']['bundle_' . $bundle_name][$bundle_name] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Fields for @bundle_name', ['@bundle_name' => $bundle_name]),
            '#default_value' => $this->getDefaultFields($bundle_name),
            '#options' => $options,
          ];
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $form_values = $form_state->getValues();

    // Build an array of excluded fields for each bundle.
    $bundle_names = [];
    foreach (array_filter($form_values['bundle_names']) as $type) {
      if (!empty(array_filter($form_values[$type]))) {
        $bundle_names[$type] = array_values(array_filter($form_values[$type]));
      }
    }

    // Save config.
    $this->config('duplicate_node.settings')->set('exclude.' . $this->getEntityTypeId(), $bundle_names)->save();

    // Display a success message depending on form_id.
    if ($form['#form_id'] === 'duplicate_node_paragraph_setting_form') {
      $this->messenger()->addMessage($this->t('Quick Node Paragraph Duplicate Settings have been saved.'));
    }
    else {
      $this->messenger()->addMessage($this->t('Duplicate Node Settings have been saved.'));
    }
  }

  /**
   * AJAX callback function to return the excluded fields part of the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The excluded fields form array.
   */
  public static function fieldsCallback(array $form, FormStateInterface $form_state) {
    return $form['exclude']['fields'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundles() {
    static $bundles;
    if (!isset($bundles)) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($this->getEntityTypeId());
    }

    return $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectedBundles(FormStateInterface $form_state) {
    $selected_types = NULL;
    $config_name = 'exclude.' . $this->getEntityTypeId();
    if (!empty($form_state->getValue('bundle_names'))) {
      $selected_types = $form_state->getValue('bundle_names');
    }
    elseif (!empty($this->getSettings($config_name)) && array_filter($this->getSettings($config_name))) {
      $selected_types = $this->getSettings($config_name);
    }

    return $selected_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(FormStateInterface $form_state) {
    $desc = $this->t('No content types selected');
    $config_name = 'exclude.' . $this->getEntityTypeId();
    if (!empty($form_state->getValue('bundle_names')) && array_filter($form_state->getValue('bundle_names'))) {
      $desc = '';
    }
    elseif (!empty($this->getSettings($config_name)) && array_filter($this->getSettings($config_name))) {
      $desc = '';
    }

    return $desc;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFields($value) {
    $default_fields = [];
    $config_name = 'exclude.' . $this->getEntityTypeId() . '.' . $value;
    if (!empty($this->getSettings($config_name))) {
      $default_fields = $this->getSettings($config_name);
    }

    return $default_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings($value) {
    $settings = $this->configFactory->get('duplicate_node.settings')->get($value);

    return $settings;
  }

}
