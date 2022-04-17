<?php

namespace DagLab\RoboDeploy\Model;

class Environment implements EnvironmentInterface {

  /**
   * @var string
   */
  protected $name;

  /**
   * @var string
   */
  protected $repoRoot;

  /**
   * @var \DagLab\RoboDeploy\Model\VersionFileInterface
   */
  protected $versionFile;

  /**
   * @var \DagLab\RoboDeploy\Model\ReleaseDetailsInterface
   */
  protected $releaseDetails;

  /**
   * @var \DagLab\RoboDeploy\Model\DeploymentDetailsInterface
   */
  protected $deploymentDetails;

  /**
   * @param string $name
   * @param string $repo_root
   */
  public function __construct(string $name, string $repo_root) {
    $this->name = $name;
    $this->repoRoot = $repo_root;
  }

  /**
   * @inheritDoc
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @inheritDoc
   */
  public function getRepoRoot() {
    return $this->repoRoot;
  }

  /**
   * @inheritDoc
   */
  public function getVersionFile() {
    return $this->versionFile;
  }

  /**
   * @inheritDoc
   */
  public function setVersionFile(VersionFileInterface $version_file) {
    $this->versionFile = $version_file;
  }

  /**
   * @inheritDoc
   */
  public function getReleaseDetails() {
    return $this->releaseDetails;
  }

  /**
   * @inheritDoc
   */
  public function setReleaseDetails(ReleaseDetailsInterface $release_details) {
    $this->releaseDetails = $release_details;
  }

  /**
   * @inheritDoc
   */
  public function isReleaseEnvironment() {
    return !empty($this->getReleaseDetails());
  }

  /**
   * @inheritDoc
   */
  public function getDeploymentDetails() {
    return $this->deploymentDetails;
  }

  /**
   * @inheritDoc
   */
  public function setDeploymentDetails(DeploymentDetailsInterface $deployment_details) {
    $this->deploymentDetails = $deployment_details;
  }

  /**
   * @inheritDoc
   */
  public function isDeploymentEnvironment() {
    return !empty($this->getDeploymentDetails());
  }

}