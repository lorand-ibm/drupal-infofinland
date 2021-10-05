<?php

namespace Drupal\helfi_ptv_integration\Commands;

use Drupal\helfi_ptv_integration\HelfiPTV;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class PTVDrushCommands extends DrushCommands
{

  /**
   * Drush command that displays the given text.
   * @param string $date
   *   Argument with the date to be used
   * @command ptv_migrate_custom_commands:ptv_migrate
   * @aliases drush-ptv_migrate ptv_migrate
   * @option uppercase
   *   Uppercase the message.
   * @usage ptv_migrate_custom_commands: ptv_migrate --uppercase  text
   */
  public function ptv_migrate($date = '1970-01-01')
  {
    $migrate = (new HelfiPTV)->getOfficeIdsPerCity('837', $date);
    $this->output()->writeln('Migration finished for date ' . $date);
  }


}
