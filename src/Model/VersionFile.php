<?php

namespace DagLab\RoboDeploy\Model;

class VersionFile implements VersionFileInterface {

  /**
   * @var bool
   */
  protected $exists = FALSE;

  /**
   * @var \SplFileInfo
   */
  protected $fileinfo;

  public function __construct(string $filename) {
    $this->exists = \file_exists($filename);
    $this->setFile($filename);
  }

  /**
   * @inheritDoc
   */
  public function setFile(string $filename) {
    $this->fileinfo = new \SplFileInfo($filename);
  }

  /**
   * @inheritDoc
   */
  public function getFileInfo() {
    return $this->fileinfo;
  }

  /**
   * @inheritDoc
   */
  public function exists() {
    return $this->exists;
  }

  /**
   * @inheritDoc
   */
  public function getName() {
    return $this->getFileInfo()->getBasename();
  }

  /**
   * @inheritDoc
   */
  public function getPath() {
    return $this->getFileInfo()->getPathname();
  }

}