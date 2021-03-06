<?php

namespace Drupal\modal_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Modal widget.
 *
 * @FieldWidget(
 *   id = "modal_widget",
 *   label = @Translation("Modal widget"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = false
 * )
 */
class ModalWidget extends WidgetBase {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs an InlineEntityFormBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $defaults = parent::defaultSettings();
    $defaults += [
      'form_mode' => 'default',
      'width' => '800',
      'height' => '500',
      'override_label' => FALSE,
      'label_singular' => '',
      'override_modal_title' => '',
      'modal_title' => '',
    ];

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['form_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Form mode'),
      '#default_value' => $this->getSetting('form_mode'),
      '#options' => $this->getFormModes(),
    ];

    $element['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Modal width'),
      '#default_value' => $this->getSetting('width'),
    ];

    $element['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Modal height'),
      '#default_value' => $this->getSetting('height'),
    ];

    $element['override_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override label'),
      '#default_value' => $this->getSetting('override_label'),
    ];

    $element['label_singular'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Singular label'),
      '#default_value' => $this->getSetting('label_singular'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][override_label]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['override_modal_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override modal title'),
      '#default_value' => $this->getSetting('override_modal_title'),
    ];

    $element['modal_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal title'),
      '#default_value' => $this->getSetting('modal_title'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][override_modal_title]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('form_mode')) {
      $summary[] = $this->t('Mode: @form_mode', ['@form_mode' => $this->getSetting('form_mode')]);
    }

    if ($this->getSetting('width')) {
      $summary[] = $this->t('Width: @width', ['@width' => $this->getSetting('width')]);
    }
    else {
      $summary[] = $this->t('Width: not set.');
    }

    if ($this->getSetting('height')) {
      $summary[] = $this->t('Height: @height', ['@height' => $this->getSetting('height')]);
    }
    else {
      $summary[] = $this->t('Height: not set.');
    }

    if ($this->getSetting('override_label')) {
      $summary[] = $this->t('Overriden label is used: %singular', ['%singular' => $this->getSetting('label_singular')]);
    }
    else {
      $summary[] = $this->t('Default label is used.');
    }

    if ($this->getSetting('override_modal_title')) {
      $summary[] = $this->t('Overriden modal title is used: %modal_title', ['%modal_title' => $this->getSetting('modal_title')]);
    }
    else {
      $summary[] = $this->t('Default modal title is used.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if (!$items->first()->getValue()) {
      return;
    }

    $entity_type = $items->getSetting('target_type');

    $url = Url::fromRoute('modal_widget.modal', [
      'entity_type' => $entity_type,
      'entity_id' => $items->first()->getValue()['target_id'],
      'form_mode' => $this->getSetting('form_mode'),
    ]);

    if ($this->getSetting('override_label')) {
      $title = $this->getSetting('label_singular');
    }
    else {
      $title = $this->t('Edit @entity-type', [
        '@entity-type' => ucfirst(str_replace('_', ' ', $entity_type)),
      ]);
    }

    if ($this->getSetting('override_modal_title')) {
      $modal_title = $this->getSetting('modal_title');
    }
    else {
      $modal_title = $title;
    }

    return [
      '#type' => 'link',
      '#title' => $title,
      '#url' => $url,
      '#ajax' => [
        'dialogType' => 'modal',
        'dialog' => [
          'width' => $this->getSetting('width'),
          'height' => $this->getSetting('height'),
          'title' => $modal_title,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->getCardinality() == 1;
  }

  /**
   * Gets all available form modes.
   *
   * @return array
   *   The form modes.
   */
  protected function getFormModes() {
    $target_type = $this->fieldDefinition->getSetting('target_type');
    $form_modes = $this->entityDisplayRepository->getFormModes($target_type);

    $modes = [];
    $modes['default'] = 'Default';

    foreach ($form_modes as $form_mode_key => $form_mode) {
      if (!isset($modes[$form_mode_key])) {
        $modes[$form_mode_key] = $form_mode['label'];
      }
    }

    return $modes;
  }

}
