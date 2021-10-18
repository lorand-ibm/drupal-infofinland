<?php

namespace Drupal\helfi_ptv_integration\Commands;

use Drupal\helfi_ptv_integration\HelfiPTV;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
class PTVDrushCommands extends DrushCommands {

  /***
   * @var HelfiPTV
   */
  protected HelfiPTV $ptv;

  public function __construct(HelfiPTV $ptv)
  {
    $this->ptv = $ptv;
    parent::__construct();
  }

  /**
   * Drush command that displays the given text.
   * @param string $date
   *   Argument with the date to be used
   * @command ptv_migrate_custom_commands:ptv_migrate
   * @aliases drush-ptv_migrate ptv_migrate
   * @usage ptv_migrate_custom_commands: ptv_migrate date
   */
  public function ptv_migrate($date = '1970-01-01') {
    $migrate = $this->ptv->getOfficeIdsPerCity('853');
    $this->output()->writeln('Migration finished for date ' . $date);
  }
}
