<?php

namespace RimVG\wreg;

use RimVG\wreg\RunBackup;
use RimVG\wreg\syntSQL\DataWorldException;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\format\io\data\BaseNbtWorldData;
use pocketmine\Server;

final class SimpleBackup
{

  static function createBackup(string $newName, string $worldName): void
  {
    $worldManager = Server::getInstance()->getWorldManager();
    if (!$worldManager->isWorldGenerated($newName)) {
      throw new LoadedWorldException("The world i try to copy is not generated");
    }
    if (!$worldManager->isWorldGenerated($worldName)) {
      throw new LoadedWorldException("The world i try to copy is not generated");
    }
    if (!$worldManager->isWorldLoaded($worldName)) {
      Server::getInstance()->getLogger()->warning("The world i try to copy is not loaded");
      return;
    }
    
    $destination = Server::getInstance()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $newName;
    $source = Server::getInstance()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $worldName;
    try {
      $this->copyDirectory($destination, $source);
    }catch(Exception $exception) {
      Server::getInstance()->getLogger()->info($exception->getMessage());
    }
    if (!Server::getInstance()->getWorldManager()->isWorldLoaded($newName)) {
      Server::getInstance()->getWorldManager()->loadWorld($newName);
    }
    $world = Server::getInstance()->getWorldManager()->getWorldByName($newName);
    
    if (empty($world)) {
      Server::getInstance()->getLogger()->error("The world does not exist of the new name");
      return;
    }
    $worldData = $world->getProvider()->getWorldData();
    
    if (!$worldData instanceof BaseNbtWorldData) {
      Server::getInstance()->getLogger()->error("BaseNbtWorldData instance not given");
      return;
    }

    $worldData->getCompoundTag()->setString("LevelName", $newName);
    Server::getInstance()->getWorldManager()->unloadWorld($world);
    Server::getInstance()->getWorldManager()->loadWorld($newName);
  }

  public static function deleteBackup(string $directory): bool
  {
    if (!is_dir($directory)) {
      return false;
    }
    Server::getInstance()->getAsyncPool()->submitTask(new DeleteBackup($directory));
    return true;
  }

  public function copyDirectory($dest, $source): void

  {
    if (is_dir($source)) {
      mkdir($dest);
      $d = dir($source);
      while(FALSE !== ($entry = $d->read())) {
        if ($entry === "." or $entry === "..") {
          continue;
        }
        $newEntry = $source . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($newEntry)) {
          $this->copyDirectory($dest . DIRECTORY_SEPARATOR . $entry, $newEntry);
          continue;
        }
        copy($newEntry, $dest . DIRECTORY_SEPARATOR . $entry);
      }
      $d->close();
    } else {
      copy($source, $dest);
    }
  }

}