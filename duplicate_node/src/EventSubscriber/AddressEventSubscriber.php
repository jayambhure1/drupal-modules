<?php

namespace Drupal\duplicate_node\EventSubscriber;

use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\InitialValuesEvent;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\duplicate_node\DuplicateNodeFinder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Support for duplicating address data.
 *
 * Provides an event subscriber to add initial values to address fields when
 * duplicating. This method is needed because of the way address handles its fields,
 * otherwise we would be doing this sort of thing inside the form builder when
 * duplicating.
 */
class AddressEventSubscriber implements EventSubscriberInterface {

  /**
   * The Private Temp Store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * Duplicate Node Node Finder.
   *
   * @var \Drupal\duplicate_node\DuplicateNodeFinder
   */
  protected $DuplicateNodeFinder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('duplicate_node.node_finder')
    );
  }

  /**
   * AddressEventSubscriber constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $privateTempStoreFactory
   *   Private temp store factory.
   * @param \Drupal\duplicate_node\DuplicateNodeFinder $DuplicateNodeFinder
   *   Duplicate Node Node Finder.
   */
  public function __construct(PrivateTempStoreFactory $privateTempStoreFactory, DuplicateNodeFinder $DuplicateNodeFinder) {
    $this->privateTempStoreFactory = $privateTempStoreFactory;
    $this->DuplicateNodeFinder = $DuplicateNodeFinder;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events = [];

    if (!class_exists('\Drupal\address\Event\AddressEvents')) {
      return $events;
    }
    if (defined('AddressEvents::INITIAL_VALUES')) {
      $events[AddressEvents::INITIAL_VALUES][] = ['onInitialValues'];
    }

    return $events;
  }

  /**
   * Generate a set of initial values.
   *
   * @return array
   *   The initial values.
   */
  public function getInitialValues($event) {
    $tempstore = $this->privateTempStoreFactory->get('duplicate_node');

    if ($tempstore->get('address_initial_value_delta') == NULL) {
      $tempstore->set('address_initial_value_delta', 0);
    }

    $node = $this->DuplicateNodeFinder->findNodeFromCurrentPath();

    if ($node == NULL) {
      return [];
    }

    $address = [];

    $delta = $tempstore->get('address_initial_value_delta');

    foreach ($node->getFieldDefinitions() as $field_definition) {
      $field_storage_definition = $field_definition->getFieldStorageDefinition();
      $field_name = $field_storage_definition->getName();

      if ($field_storage_definition->getType() == "address") {

        if (!$node->get($field_name)->isEmpty()) {

          foreach ($node->get($field_name) as $key => $value) {
            if ($key == $delta) {
              $address = [
                'country_code' => $value->getCountryCode(),
                'postal_code' => $value->getPostalCode(),
                'administrative_area' => $value->getAdministrativeArea(),
                'locality' => $value->getLocality(),
                'dependent_locality' => $value->getDependentLocality(),
                'sorting_code' => $value->getSortingCode(),
                'address_line1' => $value->getAddressLine1(),
                'address_line2' => $value->getAddressLine2(),
                'organization' => $value->getOrganization(),
                'additional_name' => $value->getAdditionalName(),
                'given_name' => $value->getGivenName(),
                'family_name' => $value->getFamilyName(),
              ];
            }

          }
        }
      }
    }

    $delta++;
    $tempstore->set('address_initial_value_delta', $delta);
    return $address;
  }

  /**
   * Alters the initial values.
   *
   * @param \Drupal\address\Event\InitialValuesEvent $event
   *   The initial values event.
   */
  public function onInitialValues(InitialValuesEvent $event) {
    $event->setInitialValues($this->getInitialValues($event));
  }

}
