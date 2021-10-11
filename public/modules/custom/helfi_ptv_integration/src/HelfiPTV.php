<?php

declare(strict_types = 1);

namespace Drupal\helfi_ptv_integration;

use DateTime;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Connection;
use Drupal\Core\State\State;
use Drupal\node\Entity\Node;
use GuzzleHttp\Client;

/**
 * Source plugin for retrieving data from PTV.
 *
 */
class HelfiPTV {

  /**
   * Guzzle Http Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * @var \Drupal\Core\State\
   */
  protected $state;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $connection;

  /**
   * Constructs a new Class.
   *
   * The http_client.
   * @param Client $httpClient
   * @param Connection $connection
   * @param State $state
   */
  public function __construct (Client $httpClient, Connection $connection, State $state) {
    $this->httpClient = $httpClient;
    $this->state = $state;
    $this->connection = $connection;
  }


  public static function replaceAccents($str) {
    $search = explode(",",
      "ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,ø,Ø,Å,Á,À,Â,Ä,È,É,Ê,Ë,Í,Î,Ï,Ì,Ò,Ó,Ô,Ö,Ú,Ù,Û,Ü,Ÿ,Ç,Æ,Œ");
    $replace = explode(",",
      "c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,o,O,A,A,A,A,A,E,E,E,E,I,I,I,I,O,O,O,O,U,U,U,U,Y,C,AE,OE");
    return str_replace($search, $replace, $str);
  }

  public function getTheCityCodes() {
    $response = $this->httpClient->get(
      'https://api.palvelutietovaranto.suomi.fi/api/v11/CodeList/GetMunicipalityCodes'
    );

    $cities = JSON::decode($response->getBody());
    $name = '';
    $state  = $this->state;
    foreach ($cities as $city) {
      foreach ($city['names'] as $name) {
        if ($name['language'] === 'fi') {
          $name = $name['value'];
        }
      }
      $key = $this->replaceAccents('ptv_city' . '.' . $name);
      $state->set($key, $city['code']);
    }
  }

  private function getAddressData($addressesData):array {
    $nodeData = [];
    foreach ($addressesData as $address) {
      if ($address->type === 'Location') {
        if (empty($address->streetAddress->street)) {
          continue;
        }
        foreach ($address->streetAddress->street as $street) {
          $nodeData['visiting'][$street->language]['address'] = $street->value . ' ' . $address->streetAddress->streetNumber;
        }
        foreach ($address->streetAddress->postOffice as $post) {
          $nodeData['visiting'][$post->language]['city'] = $address->streetAddress->postalCode . ' ' . $post->value;
        }
        if (!empty($address->streetAddress->additionalInformation)) {
          foreach ($address->streetAddress->additionalInformation as $additional) {
            $nodeData['visiting'][$additional->language]['additional'] = $additional->value;
          }
        }
      }
      if ($address->type === 'Postal') {
        if (empty($address->postOfficeBoxAddress->postOfficeBox)) {
          continue;
        }
        foreach ($address->postOfficeBoxAddress->postOfficeBox as $poBox) {
          $nodeData['postal'][$poBox->language]['poBox'] = $poBox->value;
        }
        foreach ($address->postOfficeBoxAddress->postOffice as $post) {
          if (!isset($nodeData['postal'][$post->language]['poBox'])) {
            $nodeData['postal'][$post->language]['poBox'] = $nodeData['postal']['fi']['poBox'];
          }
          $nodeData['postal'][$post->language]['city'] = $address->postOfficeBoxAddress->postalCode . ' ' . $post->value;
        }
        if (!empty($address->postOfficeBoxAddress->additionalInformation)) {
          foreach ($address->postOfficeBoxAddress->additionalInformation as $additional) {
            $nodeData['postal'][$additional->language]['additional'] = $additional->value;
          }
        }
      }
    }
    return $nodeData;
  }

  private function getServiceHours($serviceHoursArray): array {
    foreach ($serviceHoursArray as $serviceHours) {
      if(empty($serviceHours) || $serviceHours->serviceHourType !== 'DaysOfTheWeek') {
       continue;
      }
      $hours = [];
      $hours['sv']['additional'] = '';
      $hours['fi']['additional'] = '';
      $hours['en']['additional'] = '';
      if (!empty($serviceHours->additionalInformation)) {
        foreach ($serviceHours->additionalInformation as $additional) {
          $hours[$additional->language]['additional'] = $additional->value;
        }
      }
      $hours['fi']['hours'] = '';
      $hours['en']['hours'] = '';
      $hours['sv']['hours'] = '';
      foreach ($serviceHours->openingHour as $day) {

        switch ($day->dayFrom) {
          case $day->dayFrom === 'Monday':
            $hours['fi']['hours'] .= $this->getHoursForDay('Ma', $day->from, $day->to);
            $hours['en']['hours'] .= $this->getHoursForDay('Mon', $day->from, $day->to);
            $hours['sv']['hours'] .= $this->getHoursForDay('Mån', $day->from, $day->to);
            break;
          case $day->dayFrom === 'Tuesday':
            $hours['fi']['hours'] .= $this->getHoursForDay('Ti', $day->from, $day->to);
            $hours['en']['hours'] .= $this->getHoursForDay('Tue', $day->from, $day->to);
            $hours['sv']['hours'] .= $this->getHoursForDay('Tis', $day->from, $day->to);
            break;
          case $day->dayFrom === 'Wednesday':
            $hours['fi']['hours'] .= $this->getHoursForDay('Ke', $day->from, $day->to);
            $hours['en']['hours'] .= $this->getHoursForDay('Wed', $day->from, $day->to);
            $hours['sv']['hours'] .= $this->getHoursForDay('Ons', $day->from, $day->to);
            break;
          case $day->dayFrom === 'Thursday':
            $hours['fi']['hours'] .= $this->getHoursForDay('To', $day->from, $day->to);
            $hours['en']['hours'] .= $this->getHoursForDay('Thu', $day->from, $day->to);
            $hours['sv']['hours'] .= $this->getHoursForDay('Tor', $day->from, $day->to);
            break;
          case $day->dayFrom === 'Friday':
            $hours['fi']['hours'] .= $this->getHoursForDay('Pe', $day->from, $day->to);
            $hours['en']['hours'] .= $this->getHoursForDay('Fri', $day->from, $day->to);
            $hours['sv']['hours'] .= $this->getHoursForDay('Fre', $day->from, $day->to);
            break;
          case $day->dayFrom === 'Saturday':
            $hours['fi']['hours'] .= $this->getHoursForDay('La', $day->from, $day->to);
            $hours['en']['hours'] .= $this->getHoursForDay('Sat', $day->from, $day->to);
            $hours['sv']['hours'] .= $this->getHoursForDay('Lör', $day->from, $day->to);
            break;
          case $day->dayFrom === 'Sunday':
            $hours['fi']['hours'] .= $this->getHoursForDay('Su', $day->from, $day->to);
            $hours['en']['hours'] .= $this->getHoursForDay('Sun', $day->from, $day->to);
            $hours['sv']['hours'] .= $this->getHoursForDay('Sön', $day->from, $day->to);
            break;
        }
      }
      if (isset($fieldStrings)) {
        $fieldStrings['fi'] .= $hours['fi']['additional'] . ' ' . $hours['fi']['hours'];
        $fieldStrings['en'] .= $hours['en']['additional'] . ' ' . $hours['en']['hours'];
        $fieldStrings['sv'] .= $hours['sv']['additional'] . ' ' . $hours['sv']['hours'];
      } else {
        $fieldStrings['fi'] = $hours['fi']['additional'] . ' ' . $hours['fi']['hours'];
        $fieldStrings['en'] = $hours['en']['additional'] . ' ' . $hours['en']['hours'];
        $fieldStrings['sv'] = $hours['sv']['additional'] . ' ' . $hours['sv']['hours'];
      }

    }
    return isset($fieldStrings) ? $fieldStrings : '';
  }

  /**
   * @param $day
   * @param $from
   * @param $to
   * @return string
   */
  private function getHoursForDay($day, $from, $to): string {
    return $day . ' ' . substr($from, 0, -3) . '-' . substr($to, 0, -3) . ' ';
  }

  /**
   * @param $phoneNumbers
   * @return array
   */
  private function getPhoneNumbers($phoneNumbers): array {
    $phoneData = [];
    foreach ($phoneNumbers as $phoneNumber) {
      if ($phoneNumber->type !== 'Phone') {
        continue;
      }
      $additional = $phoneNumber->additionalInformation !== null ? $phoneNumber->additionalInformation . ' ' : '';
      $phoneData[$phoneNumber->language][] = $additional . $phoneNumber->prefixNumber . $phoneNumber->number;
    }
    return $phoneData;
  }

  /**
   * @param $emailAddresses
   * @return array
   */
  private function getEmails($emailAddresses): array {
    $emailData = [];
    foreach ($emailAddresses as $emailAddress) {
      $emailData[$emailAddress->language][] = $emailAddress->value;
    }
    return $emailData;
  }

  /**
   * @param $cityId
   * @param $date
   * @return mixed
   */
  private function makeOfficeIDsCall($cityId, $date) {
    $dateTime = new DateTime($date);
    if ($cityId == 'all') {
      $query = $this->connection->select('key_value', 'kv');
      $query->addField('kv', 'code');
      $query->condition('name', '%ptv_city%', 'LIKE');
      $idData = $query->execute()->fetchAll();
      foreach ($idData as $data) {
        $code = unserialize($data->code);
        $params = [
          'query' => [
            'includeWholeCountry' => 'true',
            'showHeader' => 'false',
            'date' => $dateTime->format('Y-m-d\TH:i:s')
          ]
        ];
        $response = $this->httpClient->get(
          'https://api.palvelutietovaranto.suomi.fi/api/v11/ServiceChannel/area/Municipality/code/' . $code,
          $params
        );
        $bodyData[] = JSON::decode($response->getBody());
        $pages = $response->getHeader('pageCount');
        if ($pages > 1) {
          for ($p = 1; $p <= $pages; $p++) {
            $params['query']['page'] = $p;
            $response = $this->httpClient->get(
              'https://api.palvelutietovaranto.suomi.fi/api/v11/ServiceChannel/area/Municipality/code/' . $code,
              $params
            );
            $bodyData[] = JSON::decode($response->getBody());
          }
        }
      }

    } else {
      $params = [
        'query' => [
          'includeWholeCountry' => 'true',
          'showHeader' => 'true',
          'date' => $dateTime->format('Y-m-d\TH:i:s')
        ]
      ];
      $response = $this->httpClient->get(
        'https://api.palvelutietovaranto.suomi.fi/api/v11/ServiceChannel/area/Municipality/code/' . $cityId,
        $params
      );
      $body = JSON::decode($response->getBody());
      $bodyData = $body['itemList'];
      if ($body['pageCount'] > 1) {
        for ($p = 2; $p <= $body['pageCount']; $p++) {
          $params['query']['page'] = $p;
          $response = $this->httpClient->get(
            'https://api.palvelutietovaranto.suomi.fi/api/v11/ServiceChannel/area/Municipality/code/' . $cityId,
            $params
          );
          $body = JSON::decode($response->getBody());
          foreach ($body['itemList'] as $item) {
            $bodyData[] = $item;
          }
        }
      }
    }
    return $bodyData;
  }

  private function getExistingOfficeIds(): array {
    $query = $this->connection->select('node__field_office_id', 'foi');
    $query->addField('foi', 'field_office_id_value');
    $query->condition('deleted', '1', '!=');
    return $query->execute()->fetchAllAssoc('field_office_id_value');
  }

  public function getOfficeIdsPerCity($cityId = 'all', $date = '1970-01-01') {

    $officeIds = $this->makeOfficeIDsCall($cityId, $date);
    $existingIds = $this->getExistingOfficeIds();
    foreach ($officeIds as $id) {
      if (array_key_exists($id['id'], $existingIds)) {
        continue;
      }
      $nodeData = [];
      $additionalData = $this->httpClient->get(
        'https://api.palvelutietovaranto.suomi.fi/api/v11/ServiceChannel/' . $id['id']
      );
      if ($additionalData->getStatusCode() === 200) {
        $data = json_decode($additionalData->getBody()->getContents());
        if ($data->serviceChannelType != 'ServiceLocation') {
          continue;
        }
        if (isset($data->addresses) && !empty($data->addresses)) {
          $nodeData['addresses'] = $this->getAddressData($data->addresses);
        }
        if (isset($data->deliveryAddresses) && !empty($data->deliveryAddresses)) {
          $nodeData['addresses'] = $this->getAddressData($data->deliveryAddresses);
        }
        if (isset($data->serviceHours) && !empty($data->serviceHours)) {
          $nodeData['hours'] = $this->getServiceHours($data->serviceHours);
        }
        if (isset($data->phoneNumbers) && !empty($data->phoneNumbers)) {
          $nodeData['phone'] = $this->getPhoneNumbers($data->phoneNumbers);
        }
        if (isset($data->supportPhoneNumbers) && !empty($data->supportPhoneNumbers)) {
          $nodeData['phone'] = $this->getPhoneNumbers($data->supportPhoneNumbers);
        }
        if (isset($data->emails) && !empty($data->emails)) {
          $nodeData['emails'] = $this->getEmails($data->emails);
        }
        if (isset($data->supportEmails) && !empty($data->supportEmails)) {
          $nodeData['emails'] = $this->getEmails($data->supportEmails);
        }
      }


      $node = Node::create(['type' => 'office_contact_info']);
      if (isset($nodeData['addresses'])) {
        if (isset($nodeData['addresses']['visiting'])) {
          foreach (array_keys($nodeData['addresses']['visiting']) as $language) {
            if (!in_array($language, ['fi', 'sv', 'en'])) {
              continue;
            }
            if ($language === 'fi') {
              $node->langcode = $language;
              $node->uid = 1;
              $node->promote = 0;
              $node->sticky = 0;
            }
            else if (isset($nid)) {
              $node = Node::load($nid);
              $node = $node->addTranslation($language);
            }
            $node->title = $id['name'];
            $node->field_office_id = $id['id'];
            if (isset($nodeData['addresses']['visiting'][$language]) && !empty($nodeData['addresses']['visiting'][$language])) {
              $node->field_visiting_address = $nodeData['addresses']['visiting'][$language]['address'] . ', ' . $nodeData['addresses']['visiting'][$language]['city'];
              $node->field_visiting_address_additiona = isset($nodeData['addresses']['visiting'][$language]['additional']) ?  $nodeData['addresses']['visiting'][$language]['additional'] : '';
            }
            if (isset($nodeData['addresses']['postal'][$language]) && !empty($nodeData['addresses']['postal'][$language])) {
              $node->field_postal_address = $nodeData['addresses']['postal'][$language]['poBox'] . ', ' . $nodeData['addresses']['postal'][$language]['city'];
              $node->field_postal_address_additiona = isset($nodeData['addresses']['postal'][$language]['additional']) ? $nodeData['addresses']['postal'][$language]['additional'] : '';
            }
            if (isset($nodeData['hours'][$language])) {
              $node->field_service_hours = $nodeData['hours'][$language];
            }
            if (isset($nodeData['phone'][$language])) {
              $node->field_phonenumber = $nodeData['phone'][$language];
            }
            if (isset($nodeData['emails'][$language])) {
              $node->field_email_address	 = $nodeData['emails'][$language];
            }

            $node->save();
            if ($language === 'fi') {
              $nid = $node->id();
            }
          }
        }
      }
    }
  }
}
