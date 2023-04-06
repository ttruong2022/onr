<?php

namespace Drupal\onr_views_sort\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Configuration for onr_views_sort.
 */
class Settings extends ConfigFormBase {

  // Config item.
  const CONFIG_NAME = 'onr_views_sort.settings';

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'onr_views_sort_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get(self::CONFIG_NAME);

    $form['views_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Views'),
      '#open' => FALSE,
    ];

    $views = Views::getViewsAsOptions();

    $form['views_container']['replace_views'] = [
      '#type' => 'checkboxes',
      '#options' => $views,
      '#default_value' => $config->get('replace_views'),
      '#title' => $this->t('Which views to apply to??'),
    ];

    $form['replace_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to ignore in sorting'),
      '#default_value' => $config->get('replace_text'),
      '#size' => 60,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Core\Config\ConfigValueException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $replace_views = array_filter($form_state->getValue('replace_views'));
    $this->configFactory()->getEditable(self::CONFIG_NAME)
      ->set('replace_views', $replace_views)
      ->set('replace_text', $form_state->getValue('replace_text'))
      ->save();
  }

}
