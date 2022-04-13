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
  protected $appDefaultBranch;
  protected $versionFile;
  protected $versionStrategies = ['datetime', 'incremental'];
  protected $versionStrategy = 'datetime';
  protected $versionFilename = '.robo-deploy-version';

  /**
   * RoboFile constructor.
   */
  public function __construct() {
    $this->appRoot = $this->requireConfigVal('app.root');
    $this->appDefaultBranch = $this->requireConfigVal('app.default_branch');

    if ($this->getConfigVal('version.filename')) {
      $this->versionFilename = $this->getConfigVal('version.filename');
    }
    if ($this->getConfigVal('version.strategy') && in_array($this->getConfigVal('version.strategy'), $this->versionStrategies)) {
      $this->versionStrategy = $this->getConfigVal('version.strategy');
    }

    $this->versionFile = rtrim($this->appRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->versionFilename;
  }

  /**
   *
   * @return void
   * @throws \Robo\Exception\TaskException
   */
  public function configValidate() {
    dump([
      'current app branch' => $this->getAppGitBranch(),
      'current app version' => $this->getAppVersion(),
      'version strategy' => $this->versionStrategy,
      'version file' => $this->versionFile,
      'version file exists' => file_exists($this->versionFile),
    ]);
  }

  /**
   * @param array $options
   *
   * @return void
   * @throws \Robo\Exception\TaskException
   */
  public function releaseCommit(array $options = [
    'message|m' => NULL,
  ]) {
    if (empty($options['message'])) {
      $options['message'] = 'Creating release commit.';
    }

    $this->incrementVersion();
    $this->taskGitStack()
      ->stopOnFail()
      ->dir($this->appRoot)
      ->add('-A')
      ->commit($options['message'])
      ->push('origin', $this->getAppGitBranch())
      ->run();
  }

  /**
   * @return void
   */
  public function releaseTag() {
    $version = $this->getAppVersion();
    $this->taskGitStack()
      ->stopOnFail()
      ->dir($this->appRoot)
      ->tag($version)
      ->push('origin', $version)
      ->run();
  }

  /**
   * @return string
   */
  protected function getAppVersion() {
    return $this->taskSemVer($this->versionFile)->__toString();
  }

  /**
   * @return string|null
   * @throws \Robo\Exception\TaskException
   */
  public function getAppGitBranch() {
    $result = $this->taskExec('git')
      ->dir($this->appRoot)
      ->args('symbolic-ref', 'HEAD')
      ->printOutput(false)
      ->run();

    if (empty($result->getOutputData())) {
      return $this->appDefaultBranch;
    }

    $parts = explode('/', $result->getOutputData());
    return end($parts);
  }

  /**
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