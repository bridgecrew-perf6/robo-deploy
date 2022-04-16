<?php

namespace DagLab\RoboDeploy\Model;

interface DeploymentDetailsInterface {

  /**
   * Deployment remote name.
   *
   * @return string
   */
  public function getRemote();

  /**
   * Deployment branch name.
   *
   * @return string
   */
  public function getBranch();

  /**
   * @param string $branch_name
   *
   * @return void
   */
  public function setBranch(string $branch_name);

}