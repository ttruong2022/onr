<?php

namespace Drupal\easy_responsive_images_media_embed\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Easy Responsive Images Embed form.
 */
class EasyResponsiveImageEmbedForm extends ConfigFormBase {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected EntityDisplayRepositoryInterface $entityDisplayRepository;

  /**
   * Constructs a StylesFilterConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($config_factory);
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('config.factory'),
      $container->get('entity_display.repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('easy_responsive_images_media_embed.settings');
    $view_mode_options = $this->entityDisplayRepository->getViewModeOptions('media');

    $form['easy_responsive_images_media_embed_allowed_view_modes'] = [
      '#title' => $this->t("View modes max-width field should appear for"),
      '#type' => 'checkboxes',
      '#options' => $view_mode_options,
      '#default_value' => $config->get('easy_responsive_images_media_embed_allowed_view_modes') ?? [],
      '#description' => $this->t("Select view modes that the max-width + max-height fields should appear on when embedding media. If none is selected all will be used."),
      '#element_validate' => [[static::class, 'validateOptions']],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form element validation handler.
   *
   * @param array $element
   *   The allowed_view_modes form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateOptions(array &$element, FormStateInterface $form_state) {
    // Filters the #value property so only selected values appear in the
    // config.
    $form_state->setValueForElement($element, array_filter($element['#value']));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Config\ConfigValueException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('easy_responsive_images_media_embed.settings');

    if (empty($form_state->getValue('easy_responsive_images_media_embed_allowed_view_modes'))) {
      $view_modes = $form['easy_responsive_images_media_embed_allowed_view_modes']['#options'];
    }
    else {
      $view_modes = $form_state->getValue('easy_responsive_images_media_embed_allowed_view_modes');
    }
    $config->set('easy_responsive_images_media_embed_allowed_view_modes', $view_modes);
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'easy_responsive_images_media_embed_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['easy_responsive_images_media_embed.settings'];
  }

}
