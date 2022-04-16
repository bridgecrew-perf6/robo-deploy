<?php

namespace DagLab\RoboDeploy\Model;

interface VersionFileInterface {

  /**
   * @return \SplFileInfo
   */
  public function getFileInfo();

  /**
   * @param string $filename
   *   Absolute path to version file.
   *
   * @return void
   */
  public function setFile(string $filename);

  /**
   * Whether the version file exists.
   *
   * @return bool
   */
  public function exists();

  /**
   * Filename.
   *
   * @return string
   *   Version  file name.
   */
  public function getName();

  /**
   * Full path to file.
   *
   * @return string
   *   Absolute path to version file.
   */
  public function getPath();

}