<?php

namespace Drupal\Tests\config_ignore\Functional;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Site\Settings;
use Drupal\Tests\BrowserTestBase;

/**
 * Class ConfigIgnoreBrowserTestBase.
 *
 * @package Drupal\Tests\config_ignore
 */
abstract class ConfigIgnoreBrowserTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_ignore',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Perform a config import from sync. folder.
   */
  public function doImport() {
    // Set up the ConfigImporter object for testing.
    $storage_comparer = new StorageComparer(
      $this->container->get('config.import_transformer')->transform(
        $this->container->get('config.storage.sync')
      ),
      $this->container->get('config.storage'),
      $this->container->get('config.manager')
    );

    $config_importer = new ConfigImporter(
      $storage_comparer->createChangelist(),
      $this->container->get('event_dispatcher'),
      $this->container->get('config.manager'),
      $this->container->get('lock'),
      $this->container->get('config.typed'),
      $this->container->get('module_handler'),
      $this->container->get('module_installer'),
      $this->container->get('theme_handler'),
      $this->container->get('string_translation')
    );

    $config_importer->reset()->import();
  }

  /**
   * Perform a config export to sync. folder.
   */
  public function doExport() {
    // Setup a config sync. dir with a, more or less,  know set of config
    // entities. This is a full blown export of yaml files, written to the disk.
    $destination_storage = new FileStorage(Settings::get('config_sync_directory'));
    /** @var \Drupal\Core\Config\CachedStorage $source_storage */
    $source_storage = \Drupal::service('config.storage');
    foreach ($source_storage->listAll() as $name) {
      $destination_storage->write($name, $source_storage->read($name));
    }
  }

}
