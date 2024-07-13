<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class redis extends eqLogic {
  /*     * *************************Attributs****************************** */

  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */

  /*     * ***********************Methode static*************************** */


  public static function statusService() {
    $return = shell_exec(system::getCmdSudo() . 'systemctl status redis.service');
    $pattern = '/Active: (\w+)/';
    $status_active = '';
    if (preg_match($pattern, $return, $matches)) {
        $status_active = $matches[1];
        if($status_active == 'active'){
          $status_active = 'ok';
        }else{
          $status_active = 'nok';
        }
        log::add(__CLASS__, 'info', 'Status du service : '.$status_active);
    } else {
      log::add(__CLASS__, 'info', 'Status du service : '.$return);
    }
    return $status_active;
  }

  public static function installLocalRedis() {
    if (shell_exec(system::getCmdSudo() . ' which redis | wc -l') == 0) {
       event::add('jeedom::alert', array(
          'level' => 'warning',
          'page' => 'plugin',
          'message' => __('Installation locale du service Redis en cours', __FILE__),
       ));
       shell_exec(system::getCmdSudo() . ' apt update;' . system::getCmdSudo() . ' apt install -y redis-server');
    }
  }

  public static function installDockerRedis() {
    if (shell_exec(system::getCmdSudo() . ' which docker | wc -l') == 0) {
       throw new Exception(__("Docker n'est pas installé sur la machine", __FILE__));
    }
    if (shell_exec(system::getCmdSudo() . ' which docker-compose | wc -l') == 0) {
       throw new Exception(__("Docker-compose n'est pas installé sur la machine", __FILE__));
    }
    if (shell_exec(system::getCmdSudo() . ' docker ps | grep redis | wc -l') != 0) {
       throw new Exception(__("Un service Redis est déjà installé en local sur la machine. Veuillez le désinstaller", __FILE__));
    }
    event::add('jeedom::alert', array(
       'level' => 'warning',
       'page' => 'plugin',
       'message' => __('Installation du service Redis via Docker en cours', __FILE__),
    ));
    shell_exec(system::getCmdSudo() . ' docker run -d --name redis -p 6379:6379 redis');
  }

  public static function installRedis($_mode = 'local') {
    if ($_mode == 'remote') {
       return;
    }

    if ($_mode == 'docker') {
      if (shell_exec(system::getCmdSudo() . ' which redis | wc -l') != 0) {
         throw new Exception(__("Un service Redis est déjà installé en local sur la machine. Veuillez le désinstaller", __FILE__));
      }
      self::installDockerRedis();
   } else {
      self::installLocalRedis();
   }

  }

  public static function deamon_info() {
    $return = array();
    $return['log'] = __CLASS__;
    $return['launchable'] = 'ok';

    switch (config::byKey('mode', __CLASS__)) {
      case 'remote':
         if (empty(config::byKey('remote::protocol', __CLASS__)) || empty(config::byKey('remote::ip', __CLASS__)) || empty(config::byKey('remote::port', __CLASS__))) {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __("Veuillez renseigner l'adresse complète du redis", __FILE__);
         }
         break;
      case 'docker':
         if (shell_exec(system::getCmdSudo() . ' which redis | wc -l') != 0) {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __('Veuillez désinstaller Redis local', __FILE__);
         } 
          else if (!is_object(eqLogic::byLogicalId('redis', 'docker2'))) {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __('Veuillez installer Redis', __FILE__);
         }
         break;
      default:
         $return['launchable'] = 'ok';
         break;
   }

   $return['state'] = self::statusService();
    
    return $return;
  }

  public static function deamon_start() {
    self::deamon_stop();
    $deamon_info = self::deamon_info();
    if ($deamon_info['launchable'] != 'ok') {
      throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
    }
    log::add(__CLASS__, 'info', 'Lancement du service Redis');

    shell_exec(system::getCmdSudo() . 'systemectl start redis.service');
    $i = 0;
    while ($i < 5) {
      $deamon_info = self::deamon_info();
      if ($deamon_info['state'] == 'ok') {
          break;
      }
      sleep(1);
      $i++;
    }

    if ($i >= 5) {
      log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez le log', __FILE__) , 'unableStartDeamon');
      return false;
    }
    
    message::removeAll(__CLASS__, 'unableStartDeamon');
    return true;
  }

  public static function deamon_stop() {
    shell_exec(system::getCmdSudo() . 'systemectl stop redis.service');
    sleep(1);
  }

  /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  public static function cron5() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  public static function cron10() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
  public static function cron15() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
  public static function cron30() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
  */

  /*
  * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {}
  */
  
  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_param3($value) {
    // no return value
  }
  */

  /*
   * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
   * lors de la création semi-automatique d'un post sur le forum community
   public static function getConfigForCommunity() {
      // Cette function doit retourner des infos complémentataires sous la forme d'un
      // string contenant les infos formatées en HTML.
      return "les infos essentiel de mon plugin";
   }
   */

  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */

  /*     * **********************Getteur Setteur*************************** */
}

class redisCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  // Exécution d'une commande
  public function execute($_options = array()) {
  }

  /*     * **********************Getteur Setteur*************************** */
}
