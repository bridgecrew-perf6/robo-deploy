<?php

namespace DagLab\RoboDeploy\Model;

class ReleaseDetails implements ReleaseDetailsInterface {

  /**
   * @var string
   */
  protected $remote;

  /**
   * @var string
   */
  protected $branch;

  /**
   * @var array
   */
  protected $syncBranches;

  /**
   * @param string $remote
   * @param string $branch
   * @param array $sync_branches
   */
  public function __construct(string $remote, string $branch = NULL, array $sync_branches = []) {
    $this->remote = $remote;
    $this->branch = $branch;
    $this->syncBranches = $sync_branches;
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

  /**
   * @inheritDoc
   */
  public function getSyncBranches() {
    return $this->syncBranches;
  }

  /**
   * @inheritDoc
   */
  public function shouldSyncBranches() {
    return !empty($this->getSyncBranches());
  }

}