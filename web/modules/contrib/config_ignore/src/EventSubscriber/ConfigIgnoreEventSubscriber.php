<?php

namespace Drupal\config_ignore\EventSubscriber;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigIgnoreEventSubscriber implements EventSubscriberInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new event subscriber instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    if (Settings::get('config_ignore_deactivate')) {
      return [];
    }

    return [
      ConfigEvents::STORAGE_TRANSFORM_IMPORT => ['onImportTransform'],
      ConfigEvents::STORAGE_TRANSFORM_EXPORT => ['onExportTransform'],
    ];
  }

  /**
   * Acts when the storage is transformed for import.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onImportTransform(StorageTransformEvent $event) {
    $import_storage = $event->getStorage();
    $settings = $this->getSettings();

    // We do the processing of the config entities line-by-line.
    foreach($settings['ignored_config'] as $ignored) {
      // This returns all config names from both the active storage as the
      // storage that will be imported.
      $active_storage_names = array_filter($this->configFactory->listAll(), function($config_name) use ($ignored) {
        return $this->wildcardMatch($ignored['name'], $config_name);
      });
      $import_storage_names = array_filter($import_storage->listAll(), function($config_name) use ($ignored) {
        return $this->wildcardMatch($ignored['name'], $config_name);
      });

      // We remove all config that was marked as 'do not ignore' from these
      // arrays here. This way, they will not be imported and parsed as normal.
      $active_storage_names = array_diff($active_storage_names, $settings['included_config']);
      $import_storage_names = array_diff($import_storage_names, $settings['included_config']);

      foreach($import_storage_names as $import_storage_name) {
        // When config that would normally would be imported also exists in the
        // active config, we just replace the config to be imported with the
        // config that is in sync. This way, nothing will be imported. When a
        // $config_key is not available, we replace the complete config. When it
        // is available, we only replace the $config_key value.
        if (in_array($import_storage_name, $active_storage_names)) {
          $active_config = $this->configFactory->get($import_storage_name)->getRawData();
          if (!empty($ignored['key'])) {
            $import_config = $import_storage->read($import_storage_name);

            $config_key_exists = FALSE;
            $config_key_value = NestedArray::getValue($active_config, $ignored['key'], $config_key_exists);

            if ($config_key_exists) {
              NestedArray::setValue($import_config, $ignored['key'], $config_key_value, TRUE);
            }
            else {
              NestedArray::unsetValue($import_config, $ignored['key']);
            }

            $import_storage->write($import_storage_name, $import_config);
          }
          else {
            $import_storage->write($import_storage_name, $active_config);
          }
        }
        else {
          // When the config that would normally be imported does not exist in
          // active storage, we remove it from the config to be imported.
          if (!empty($ignored['key'])) {
            $import_config = $import_storage->read($import_storage_name);
            NestedArray::unsetValue($import_config, $ignored['key']);
            $import_storage->write($import_storage_name, $import_config);
          }
          else {
            $import_storage->delete($import_storage_name);
          }
        }
      }
      // Config that exists in both storages should already be handled in the
      // foreach of $import_storage_names, so we don't have to handle it here.
      foreach(array_diff($active_storage_names, $import_storage_names) as $active_storage_name) {
        $active_config = $this->configFactory->get($active_storage_name)->getRawData();
        // Config that is in active storage, but not in the storage that will be
        // imported, should be created on the importing storage.
        if (!empty($ignored['key'])) {
          $import_config = [];
          $config_key_exists = FALSE;
          $config_key_value = NestedArray::getValue($active_config, $ignored['key'], $config_key_exists);
          // We only set & write the active value when it's actually available,
          // so we don't create an empty config object.
          if ($config_key_exists) {
            NestedArray::setValue($import_config, $ignored['key'], $config_key_value, TRUE);
            $import_storage->write($active_storage_name, $import_config);
          }
        }
        else {
          $import_storage->write($active_storage_name, $active_config);
        }
      }
    }
  }

  /**
   * Acts when the storage is transformed for export.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onExportTransform(StorageTransformEvent $event) {
    $storage = $event->getStorage();
    $settings = $this->getSettings();

    // We do the same as in the onImportTransform, but instead of replacing
    // values with the active storage we just remove them.
    foreach($settings['ignored_config'] as $ignored) {
      $storage_names = array_filter($storage->listAll(), function($config_name) use ($ignored) {
        return $this->wildcardMatch($ignored['name'], $config_name);
      });
      $storage_names = array_diff($storage_names, $settings['included_config']);

      foreach($storage_names as $storage_name) {
        if (!empty($ignored['key'])) {
          $storage_config = $storage->read($storage_name) ?: [];
          NestedArray::unsetValue($storage_config, $ignored['key']);
          $storage->write($storage_name, $storage_config);
        }
        else {
          $storage->delete($storage_name);
        }
      }
    }
  }

  /**
   * Returns a list of settings.
   *
   * @return array[]
   *   An associative array with two keys: 'ignored_config', 'included_config'.
   *   Each value is an array of config names.
   */
  protected function getSettings() {
    /** @var string[] $ignored */
    $ignored = $this->configFactory->get('config_ignore.settings')->get('ignored_config_entities');

    $this->moduleHandler->invokeAll('config_ignore_settings_alter', [&$ignored]);

    $config_not_ignored = [];
    foreach ($ignored as $i) {
       if (substr($i, 0, 1) !== '~') {
         continue;
       }
       $config_not_ignored[] = substr($i, 1);
    }

    $ignored_config = array_diff($ignored, $config_not_ignored);
    $ignored_config = array_map(function ($config_entry) {
      // When a line does not contain a :, the $config_key will be empty.
      // Otherwise, $config_key will contain the specific key to ignore.
      $exploded_config_entry = explode(':', $config_entry, 2);
      $config_key = empty($exploded_config_entry[1]) ? [] : explode('.', $exploded_config_entry[1]);
      return [
        'name' => $exploded_config_entry[0],
        'key' => $config_key,
      ];
    }, $ignored_config);
    return [
      'ignored_config' => $ignored_config,
      'included_config' => $config_not_ignored,
    ];
  }

  /**
   * Checks if a string matches a given wildcard pattern.
   *
   * @param $pattern
   *   The wildcard pattern to me matched.
   * @param $string
   *   The string to be checked.
   *
   * @return bool
   *   TRUE if $string string matches the $pattern pattern.
   */
  protected function wildcardMatch($pattern, $string) {
    $pattern = '/^' . preg_quote($pattern, '/') . '$/';
    $pattern = str_replace('\*', '.*', $pattern);
    return (bool) preg_match($pattern, $string);
  }

}
