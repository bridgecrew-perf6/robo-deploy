<?php

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
  use \Kerasai\Robo\Config\ConfigHelperTrait;

  protected $appRoot;
  protected $isReleaseEnvironment = FALSE;
  protected $releaseRemote;
  protected $releaseBranch;
  protected $isDeploymentEnvironment = FALSE;
  protected $isTagDeployment = FALSE;
  protected $deploymentRemote;
  protected $deploymentBranch;
  protected $versionFile;
  protected $versionStrategies = ['datetime', 'incremental'];
  protected $versionStrategy = 'datetime';
  protected $versionFilename = '.robo-deploy-version';

  /**
   * RoboFile constructor.
   */
  public function __construct() {
    $this->appRoot = $this->requireConfigVal('app.root');

    $this->isReleaseEnvironment = (bool) $this->getConfigVal('release.branch');
    $this->releaseRemote = $this->getConfigVal('release.remote') ?? 'origin';
    $this->releaseBranch = $this->getConfigVal('release.branch') ?? $this->getCurrentBranch();

    $this->isDeploymentEnvironment = (bool) $this->getConfigVal('deployment.branch');
    $this->isTagDeployment = (bool) $this->getConfigVal('deployment.tag');
    $this->deploymentRemote = $this->getConfigVal('deployment.remote') ?? 'origin';
    $this->deploymentBranch = $this->getConfigVal('deployment.branch') ?? $this->getCurrentBranch();

    if ($this->getConfigVal('version.filename')) {
      $this->versionFilename = $this->getConfigVal('version.filename');
    }
    if ($this->getConfigVal('version.strategy') && in_array($this->getConfigVal('version.strategy'), $this->versionStrategies)) {
      $this->versionStrategy = $this->getConfigVal('version.strategy');
    }

    $this->versionFile = rtrim($this->appRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->versionFilename;
  }

  /**
   * Validate configuration.
   *
   * @return void
   * @throws \Robo\Exception\TaskException
   */
  public function configValidate() {
    dump([
      'current app branch' => $this->getCurrentBranch(),
      'current app version' => $this->getAppVersion(),
      'version strategy' => $this->versionStrategy,
      'version file' => $this->versionFile,
      'version file exists' => file_exists($this->versionFile),
      'release' => $this->getConfigVal('release'),
      'deployment' => $this->getConfigVal('deployment'),
    ]);
  }

  /**
   * Increment version and push commit.
   *
   * @param array $options
   *
   * @return void
   * @throws \Robo\Exception\TaskException
   */
  public function releaseCommit(array $options = [
    'message|m' => 'Creating release commit.',
  ]) {
    if (!$this->isReleaseEnvironment) {
      throw new \RuntimeException("Cannot perform release within configuration.");
    }

    $this->incrementVersion();
    $this->taskGitStack()
      ->stopOnFail()
      ->dir($this->appRoot)
      ->add('-A')
      ->checkout($this->releaseBranch)
      ->commit($options['message'])
      ->push($this->releaseRemote, $this->releaseBranch)
      ->run();
  }

  /**
   * Tag the current version and push.
   *
   * @return void
   */
  public function releaseTag() {
    if (!$this->isReleaseEnvironment) {
      throw new \RuntimeException("Cannot perform release within configuration.");
    }

    $version = $this->getAppVersion();
    $this->taskGitStack()
      ->stopOnFail()
      ->dir($this->appRoot)
      ->checkout($this->releaseBranch)
      ->tag($version)
      ->push($this->releaseRemote, $version)
      ->run();
  }

  /**
   * Get the current tracked app version.
   *
   * @return string
   */
  protected function getAppVersion() {
    return $this->taskSemVer($this->versionFile)->__toString();
  }

  /**
   * Get the name of the branch the app is on.
   *
   * @return string|null
   * @throws \Robo\Exception\TaskException
   */
  protected function getCurrentBranch() {
    $result = $this->taskExec('git')
      ->dir($this->appRoot)
      ->args('symbolic-ref', 'HEAD')
      ->printOutput(false)
      ->run();

    if (empty($result->getOutputData())) {
      return $this->releaseBranch;
    }

    $parts = explode('/', $result->getOutputData());
    return end($parts);
  }

  /**
   * Increment tracked app version.
   *
   * @return void
   * @throws \Robo\Exception\TaskException
   */
  protected function incrementVersion() {
    $semver = $this->taskSemVer($this->versionFile);
    switch ($this->versionStrategy) {
      case 'datetime':
        $date = new DateTime();
        $semver
          ->version($date->format('Y.m.d'))
          ->prerelease((string) (time() - strtotime('today')));
        break;

      case 'incremental':
        $semver->increment();
        break;
    }

    $semver->run();
  }

}