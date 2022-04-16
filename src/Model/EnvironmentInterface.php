<?php

namespace DagLab\RoboDeploy\Model;

interface EnvironmentInterface {

  /**
   * Unique name for the environment.
   *
   * @return string
   */
  public function getName();

  /**
   * Absolute path to environment's repository root folder.
   *
   * @return string
   */
  public function getRepoRoot();

  /**
   * @return \DagLab\RoboDeploy\Model\VersionFileInterface
   */
  public function getVersionFile();

  /**
   * @param \DagLab\RoboDeploy\Model\VersionFileInterface $version_file
   *
   * @return void
   */
  public function setVersionFile(VersionFileInterface $version_file);

  /**
   * Details related to an environment's release strategy.
   *
   * @return \DagLab\RoboDeploy\Model\ReleaseDetailsInterface
   */
  public function getReleaseDetails();

  /**
   * Set release details for the environment.
   *
   * @param \DagLab\RoboDeploy\Model\ReleaseDetailsInterface $release_details
   */
  public function setReleaseDetails(ReleaseDetailsInterface $release_details);

  /**
   * Whether the environment is meant as a release environment.
   *
   * @return bool
   */
  public function isReleaseEnvironment();

  /**
   * Details related to an environment's deployment strategy.
   *
   * @return \DagLab\RoboDeploy\Model\DeploymentDetailsInterface
   */
  public function getDeploymentDetails();

  /**
   * Set deployment details for the environment.
   *
   * @param \DagLab\RoboDeploy\Model\DeploymentDetailsInterface $deployment_details
   */
  public function setDeploymentDetails(DeploymentDetailsInterface $deployment_details);

  /**
   * Whether the environment is meant as a deployment environment.
   *
   * @return bool
   */
  public function isDeploymentEnvironment();

}