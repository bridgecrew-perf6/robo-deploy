<?php

namespace DagLab\RoboDeploy\Model;

interface ReleaseDetailsInterface {

  /**
   * @return string
   */
  public function getRemote();

  /**
   * @return string
   */
  public function getBranch();

  /**
   * @param string $branch_name
   *
   * @return void
   */
  public function setBranch(string $branch_name);

  /**
   * @return string[]
   */
  public function getSyncBranches();

  /**
   * @return bool
   */
  public function shouldSyncBranches();

}