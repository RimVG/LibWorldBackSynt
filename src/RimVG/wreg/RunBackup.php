<?php

namespace RimVG\wreg;

use const GLOB_MARK;
use function substr;
use function strlen;
use function glob;
use function rmdir;

use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;

class RunBackup extends AsyncTask
{
  private $directory;
  
  public function __construct(string $directory)
  {
    $this->directory = $directory;
  }
  
  public function deleteDirectory(string $directory): void
  {
    if (substr($directory, strlen($directory) - 1, 1) !== "/") {
      $directory .= DIRECTORY_SEPARATOR;
      $files = glob($directory . "*", GLOB_MARK);
      foreach($files as $file) {
        if (is_dir($file)) {
          $this->deleteDirectory($directory . DIRECTORY_SEPARATOR . $file);
        } else {
          unlink($file);
        }
      }
    }
    rmdir($directory);
  }
  
  public function onRun(): void
  {
    try {
      $this->deleteDirectory($this->directory);
    }catch(Exception $exception) {
      Server::getInstance()->getLogger()->info($exception->getMessage());
    }
  }
  
}