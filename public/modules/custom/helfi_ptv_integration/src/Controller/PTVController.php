<?php

namespace Drupal\helfi_ptv_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PTVController extends ControllerBase{

  /**
   * @var Connection
   */
  private Connection $connection;

  public function __construct(Connection $connection)
  {
    $this->connection = $connection;
  }


  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Create the list of municipality codes to be shown in admin panel
   * @return mixed
   */
  public function showCodes()
  {
    $query = $this->connection->select('key_value', 'kv');
    $query->addField('kv', 'name');
    $query->addField('kv', 'value', 'code');
    $query->condition('name', '%ptv_city%', 'LIKE');
    $idData = $query->execute()->fetchAll();
    $result = [];
    foreach ($idData as $data) {
      $result[] = ['name' => substr($data->name, 9), 'id' => unserialize($data->code)];
    }
    $header = [
      'name' => 'Kunta',
      'id' => 'ID'
    ];
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $result,
      '#empty' => $this->t('No municipalities found'),
    ];
    return $form;
  }
}
