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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
        <label class="col-md-4 control-label">{{Mode}}
          <sup><i class="fas fa-question-circle tooltips" title="{{Sélectionner le mode d'installation du broker (voir documentation)}}"></i></sup>
        </label>
        <div class="col-md-7">
          <select class="configKey form-control" data-l1key="mode">
            <option value="none" disabled>{{A configurer}}</option>
            <option value="local">{{Broker local (par défaut)}}</option>
            <!-- <option value="docker">{{Broker local docker}}</option>
            <option value="remote">{{Broker distant}}</option> -->
          </select>
        </div>
      </div>
    </div>
  </fieldset>
</form>
