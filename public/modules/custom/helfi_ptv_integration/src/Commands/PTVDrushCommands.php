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
   * Drush command that migrates office data.
   * @param string $date
   * @param string $city
   *   Argument with the date to be used
   * @command ptv_migrate_offices_custom_commands:ptv_migrate_offices
   * @aliases drush-ptv_migrate_offices ptv_migrate_offices
   * @usage ptv_migrate_custom_commands: ptv_migrate_offices date
   */
  public function ptv_migrate_offices($city = 'all', $date = '1970-01-01') {
    $migrate = $this->ptv->getOfficeIdsPerCity($city, $date);
    $this->output()->writeln('Migration finished for date ' . $date . ' and city ' . $city);
  }

  /**
   * Drush command that migrates city codes
   *   Argument with the date to be used
   * @command ptv_migrate_custom_commands:ptv_migrate_cities
   * @aliases drush-ptv_migrate_cities ptv_migrate
   * @usage ptv_migrate_cities_custom_commands: ptv_migrate_cities
   */
  public function ptv_migrate_cities() {
    $migrate = $this->ptv->getTheCityCodes();
    $this->output()->writeln('Migration finished');
  }
}
