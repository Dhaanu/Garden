<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

/**
 * A simple interface that all plugins must follow. Aside from the Setup
 * method, this is used more to identify plugins than to enforce structure upon
 * them.
 *
 *
 * @author Mark O'Sullivan
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Lussumo.Garden.Core
 */


if (!defined('APPLICATION'))
   exit();


/**
 * A simple interface that all plugins must follow. Aside from the Setup
 * method, this is used more to identify plugins than to enforce structure upon
 * them.
 *
 * @package Garden
 * @todo Could it include a Disable and Uninstall method? (from damien)
 */
interface IPlugin {


   /**
    * This method can be used to ensure that any setup components of a plugin have been set up before the
    * plugin is "enabled".
    *
    * This method will be called every time a plugin is enabled,
    * so it should check before performing redundant operations like
    * inserting tables or data into the database. If a plugin has no setup to
    * perform, simply declare this method and return TRUE.
    *
    * Returns a boolean value indicating success.
    *
    * @return boolean
    */
   public function Setup();
}