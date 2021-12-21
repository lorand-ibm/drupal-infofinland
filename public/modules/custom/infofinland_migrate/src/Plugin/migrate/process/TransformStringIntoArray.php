<?php

namespace Drupal\infofinland_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Turn string into array.
 *
 * @code
 * process:
 *   field_municipalitys:
 *     -
 *       plugin: string_to_array
 *       source: string
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "string_to_array",
 * )
 */
class TransformStringIntoArray extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $return = [];

    $array = explode(",", $value);
    foreach ($array as $v) {
      $return[] = ['name' =>  $v];
    }

    return $return;
  }

}
