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
 * Manages available applications, enabling and disabling them.
 *
 * @author Mark O'Sullivan
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Lussumo.Garden.Core
 */

class Gdn_ApplicationManager {

   /**
    * An array of available applications. Never access this directly, instead
    * use $this->AvailableApplications();
    *
    * @var array
    */
   private $_AvailableApplications = NULL;

   /**
    * An array of enabled applications. Never access this directly, instead
    * use $this->EnabledApplications();
    *
    * @var array
    */
   private $_EnabledApplications = NULL;
   
   /**
    * The valid paths to search for applications.
    *
    * @var array
    */
   public $Paths = array(PATH_APPLICATIONS);

   /**
    * Looks through the root Garden directory for valid applications and
    * returns them as an associative array of "Application Name" =>
    * "Application Info Array". It also adds a "Folder" definition to the
    * Application Info Array for each application.
    */
   public function AvailableApplications() {
      if (!is_array($this->_AvailableApplications)) {
         $ApplicationInfo = array();
         
         $AppFolders = Gdn_FileSystem::Folders(PATH_APPLICATIONS); // Get an array of all application folders
         $ApplicationAboutFiles = Gdn_FileSystem::FindAll(PATH_APPLICATIONS, 'settings' . DS . 'about.php', $AppFolders); // Now look for about files within them.
         // Include them all right here and fill the application info array
         $ApplicationCount = count($ApplicationAboutFiles);
         for ($i = 0; $i < $ApplicationCount; ++$i) {
            include($ApplicationAboutFiles[$i]);

            // Define the folder name for the newly added item
            foreach ($ApplicationInfo as $ApplicationName => $Info) {
               if (array_key_exists('Folder', $ApplicationInfo[$ApplicationName]) === FALSE) {
                  $Folder = substr($ApplicationAboutFiles[$i], strlen(PATH_APPLICATIONS));
                  if (substr($Folder, 0, 1) == DS)
                     $Folder = substr($Folder, 1);

                  $Folder = substr($Folder, 0, strpos($Folder, DS));
                  $ApplicationInfo[$ApplicationName]['Folder'] = $Folder;
               }
            }
         }
         $this->_AvailableApplications = $ApplicationInfo;
      }

      return $this->_AvailableApplications;
   }

   /**
    * @todo Undocumented method.
    */
   public function EnabledApplications() {
      if (!is_array($this->_EnabledApplications)) {
         $EnabledApplications = Gdn::Config('EnabledApplications', array('Garden' => 'garden'));
         // Add some information about the applications to the array.
         foreach($EnabledApplications as $Name => $Folder) {
            $EnabledApplications[$Name] = array('Folder' => $Folder);
            $EnabledApplications[$Name]['Version'] = Gdn::Config($Name.'.Version', '');
         }
         $this->_EnabledApplications = $EnabledApplications;
      }

      return $this->_EnabledApplications;
   }
   
   public function AvailableVisibleApplications() {
      $AvailableApplications = $this->AvailableApplications();
      foreach ($AvailableApplications as $ApplicationName => $Info) {
         if (!ArrayValue('AllowEnable', $Info, TRUE) || !ArrayValue('AllowDisable', $Info, TRUE))
            unset($AvailableApplications[$ApplicationName]);
      }
      return $AvailableApplications;
   }

   public function EnabledVisibleApplications() {
      $AvailableApplications = $this->AvailableApplications();
      $EnabledApplications = $this->EnabledApplications();
      foreach ($AvailableApplications as $ApplicationName => $Info) {
         if (array_key_exists($ApplicationName, $EnabledApplications)) {
            if (!ArrayValue('AllowEnable', $Info, TRUE) || !ArrayValue('AllowDisable', $Info, TRUE)) {
               unset($AvailableApplications[$ApplicationName]);
            }
         } else {
            unset($AvailableApplications[$ApplicationName]);
         }
      }
      return $AvailableApplications;
   }

   /**
    * @todo Undocumented method.
    */
   public function EnabledApplicationFolders() {
      $EnabledApplications = Gdn::Config('EnabledApplications', array());
      $EnabledApplications['Garden'] = 'garden';
      return array_values($EnabledApplications);
   }

   /**
    * Undocumented method.
    *
    * @param string $ApplicationName Undocumented variable.
    * @todo Document CheckRequirements() method.
    */
   public function CheckRequirements($ApplicationName) {
      $AvailableApplications = $this->AvailableApplications();
      $RequiredApplications = ArrayValue('RequiredApplications', ArrayValue($ApplicationName, $AvailableApplications, array()), FALSE);
      $EnabledApplications = $this->EnabledApplications();
      CheckRequirements($ApplicationName, $RequiredApplications, $EnabledApplications, 'application');
   }

   /**
    * Undocumented method.
    *
    * @param string $ApplicationName Undocumented variable.
    * @param string $Validation Undocumented variable.
    * @todo Document EnableApplication() method.
    */
   public function EnableApplication($ApplicationName, $Validation) {
      // Add the application to the $EnabledApplications array in conf/applications.php
      $ApplicationInfo = ArrayValue($ApplicationName, $this->AvailableApplications(), array());
      $ApplicationFolder = ArrayValue('Folder', $ApplicationInfo, '');
      if ($ApplicationFolder == '') {
         throw new Exception(Gdn::Translate('The application folder was not properly defined.'));
      } else {
         SaveToConfig('EnabledApplications'.'.'.$ApplicationName, $ApplicationFolder);
      }

      // Redefine the locale manager's settings $Locale->Set($CurrentLocale, $EnabledApps, $EnabledPlugins, TRUE);
      $PluginManager = Gdn::Factory('PluginManager');
      $Locale = Gdn::Locale();
      $Locale->Set($Locale->Current(), $this->EnabledApplicationFolders(), $PluginManager->EnabledPluginFolders(), TRUE);

      return TRUE;
   }

   /**
    * Undocumented method.
    *
    * @param string $ApplicationName Undocumented variable.
    * @todo Document DisableApplication() method.
    */
   public function DisableApplication($ApplicationName) {
      // 1. Check to make sure that this application is allowed to be disabled
      $ApplicationInfo = ArrayValue($ApplicationName, $this->AvailableApplications(), array());
      if (!ArrayValue('AllowDisable', $ApplicationInfo, TRUE))
         throw new Exception(sprintf(Gdn::Translate('You cannot disable the %s application.'), $ApplicationName));

      // 2. Check to make sure that no other enabled applications rely on this one
      foreach ($this->EnabledApplications() as $CheckingName => $CheckingInfo) {
         $RequiredApplications = ArrayValue('RequiredApplications', $CheckingInfo, FALSE);
         if (is_array($RequiredApplications) && array_key_exists($ApplicationName, $RequiredApplications) === TRUE) {
            throw new Exception(sprintf(Gdn::Translate('You cannot disable the %1$s application because the %2$s application requires it in order to function.'), $ApplicationName, $CheckingName));
         }
      }

      // 2. Disable it
      RemoveFromConfig('EnabledApplications'.'.'.$ApplicationName);

      // Redefine the locale manager's settings $Locale->Set($CurrentLocale, $EnabledApps, $EnabledPlugins, TRUE);
      $PluginManager = Gdn::Factory('PluginManager');
      $Locale = Gdn::Locale();
      $Locale->Set($Locale->Current(), $this->EnabledApplicationFolders(), $PluginManager->EnabledPluginFolders(), TRUE);
   }

   /**
    * Undocumented method.
    *
    * @param string $ApplicationName Undocumented variable.
    * @param string $Validation Undocumented variable.
    * @todo Document RegisterPermissions() method.
    */
   public function RegisterPermissions($ApplicationName, &$Validation) {
      $ApplicationInfo = ArrayValue($ApplicationName, $this->AvailableApplications(), array());
      $PermissionName = ArrayValue('RegisterPermissions', $ApplicationInfo, FALSE);
      if ($PermissionName != FALSE) {
         $PermissionModel = Gdn::PermissionModel();
         $PermissionModel->Define($PermissionName);
      }
   }

   /**
    * Call the applications setup method.
    *
    * @param string $ApplicationName Undocumented variable.
    * @param string $SenderController Undocumented variable.
    * @todo Document ApplicationSetup() method.
    */
   public function ApplicationSetup($ApplicationName, $SenderController, $Validation, $ForceReturn = FALSE) {
      $ApplicationInfo = ArrayValue($ApplicationName, $this->AvailableApplications(), array());
      $SetupController = ArrayValue('SetupController', $ApplicationInfo);
      $AppFolder = ArrayValue('Folder', $ApplicationInfo, strtolower($ApplicationName));
      if (!$SetupController)
         return TRUE;

      include(CombinePaths(array(PATH_APPLICATIONS, $AppFolder, 'controllers', $SetupController.'.php')));
      $SetupControllerName = $SetupController.'Controller';
      $SetupController = new $SetupControllerName();
      $SetupController->GetImports();
      $SetupController->ApplicationFolder = $AppFolder;
      $SetupController->View = 'index';
      $DeliveryType = GetIncomingValue('DeliveryType', DELIVERY_TYPE_ALL);
      $SetupFormPosted = $SetupController->Form->GetValue('Posted') == '1' ? TRUE : FALSE;
      $SetupController->Form->AddHidden('Posted', '1');
      // if (!$SetupFormPosted || !$SetupController->Index()) {
      if (!$SetupController->Index()) {
         if ($ForceReturn === TRUE) {
            return FALSE;
         } else {
            $View = $SetupController->FetchView();
   
            if ($DeliveryType === DELIVERY_TYPE_ALL) {
               $SenderController->AddAsset('Content', $View);
               $SenderController->RenderMaster();
            } else {
               if ($SetupController->Form->AuthenticatedPostBack()) {
                  // If the form has been posted back, send json
                  $SetupController->SetJson('FormSaved', $SetupController->Form->ErrorCount() > 0 ? FALSE : TRUE);
                  $SetupController->SetJson('Data', $View);
                  $SetupController->SetJson('StatusMessage', $SetupController->StatusMessage);
                  $SetupController->SetJson('RedirectUrl', $SetupController->RedirectUrl);
                  $Database = Gdn::Database();
                  $Database->CloseConnection();
                  exit(json_encode($SetupController->GetJson()));
               } else {
                  exit($View);
               }
            }
         }
         return FALSE;
      } else {
         $this->EnableApplication($ApplicationName, $Validation);
         return TRUE;
      }
   }

   /**
    * Undocumented method.
    *
    * @param string $ApplicationName Undocumented variable.
    * @todo Document ApplicationHasSetup() method.
    */
   public function ApplicationHasSetup($ApplicationName) {
      $ApplicationInfo = ArrayValue($ApplicationName, $this->AvailableApplications(), array());
      return ArrayValue('SetupController', $ApplicationInfo) === FALSE ? FALSE : TRUE;
   }
}