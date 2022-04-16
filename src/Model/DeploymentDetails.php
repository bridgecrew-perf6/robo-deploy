<?php

namespace DagLab\RoboDeploy\Model;

class DeploymentDetails implements DeploymentDetailsInterface {

  /**
   * @var string
   */
  protected $remote;

  /**
   * @var string
   */
  protected $branch;

  /**
   * @param string $remote
   * @param string $branch
   */
  public function __construct(string $remote, string $branch = NULL) {
    $this->remote = $remote;
    $this->branch = $branch;
  }

  /**
   * @inheritDoc
   */
  public function getRemote() {
    return $this->remote;
  }

  /**
   * @inheritDoc
   */
  public function getBranch() {
    return $this->branch;
  }

  /**
   * @inheritDoc
   */
  public function setBranch(string $branch_name) {
    $this->branch = $branch_name;
  }

}