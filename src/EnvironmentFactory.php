<?php

namespace DagLab\RoboDeploy;

use DagLab\RoboDeploy\Model\DeploymentDetails;
use DagLab\RoboDeploy\Model\Environment;
use DagLab\RoboDeploy\Model\ReleaseDetails;
use DagLab\RoboDeploy\Model\VersionFile;

class EnvironmentFactory {

  /**
   * @param string $name
   * @param array $environment_details
   * @param string $version_filename
   *
   * @return \DagLab\RoboDeploy\Model\EnvironmentInterface
   */
  static public function createFromConfig(
    string $name,
    array $environment_details,
    string $version_filename
  ) {
    $environment = new Environment(
      $environment_details['name'] ?? $name,
      $environment_details['repo_root']
    );
    $version_file = rtrim($environment->getRepoRoot(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $version_filename;
    $environment->setVersionFile(new VersionFile($version_file));

    if (isset($environment_details['release'])) {
      $environment->setReleaseDetails(new ReleaseDetails(
        $environment_details['release']['remote'] ?? 'origin',
        $environment_details['release']['branch'] ?? NULL,
        $environment_details['release']['sync_branch'] ?? []
      ));
    }

    if (isset($environment_details['deployment'])) {
      $environment->setDeploymentDetails(new DeploymentDetails(
        $environment_details['deployment']['remote'] ?? 'origin',
        $environment_details['deployment']['branch'] ?? NULL
      ));
    }

    return $environment;
  }

}