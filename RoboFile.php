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

    $this->isReleaseEnvironment = (bool) $this->getConfigVal('release');
    $this->releaseRemote = $this->getConfigVal('release.remote') ?? 'origin';
    $this->releaseBranch = $this->getConfigVal('release.branch');

    $this->isDeploymentEnvironment = (bool) $this->getConfigVal('deployment');
    //$this->isTagDeployment = (bool) $this->getConfigVal('deployment.tag');
    $this->deploymentRemote = $this->getConfigVal('deployment.remote') ?? 'origin';
    $this->deploymentBranch = $this->getConfigVal('deployment.branch');

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
    if (!$this->releaseBranch) {
      $this->releaseBranch = $this->getCurrentBranch();
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

    if ($this->getConfigVal('release.sync_branches')) {
      $sync_branches = (array) $this->getConfigVal('release.sync_branches');
      $git = $this->taskGitStack()
        ->stopOnFail()
        ->dir($this->appRoot);
      foreach ($sync_branches as $branch) {
        $git
          ->checkout($branch)
          ->merge($this->releaseBranch)
          ->push($this->releaseRemote, $branch);
      }

      $git->checkout($this->releaseBranch);
      $git->run();
    }
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
    if (!$this->releaseBranch) {
      $this->releaseBranch = $this->getCurrentBranch();
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
   * Deploy a tag of the app.
   *
   * @param array $options
   *
   * @return void
   */
  public function deployTag(array $options = [
    'reset' => TRUE,
    'tag|t' => NULL,
  ]) {
    if (!$this->isDeploymentEnvironment) {
      throw new \RuntimeException("Cannot perform deployment within configuration.");
    }

    $tag = $options['tag'];

    // Fetch changes and checkout the latest version file.
    if (!$tag) {
      $this->taskExecStack()
        ->stopOnFail()
        ->dir($this->appRoot)
        ->exec('git fetch --all --prune')
        ->exec("git checkout {$this->deploymentRemote}/{$this->deploymentBranch} -- {$this->versionFilename}")
        ->run();

      $tag = $this->getAppVersion();
    }

    if ($options['reset']) {
      $this->taskExecStack()
        ->stopOnFail()
        ->dir($this->appRoot)
        ->exec("git reset --hard {$this->deploymentRemote}/{$this->deploymentBranch}")
        ->run();
    }

    $this->taskGitStack()
      ->stopOnFail()
      ->dir($this->appRoot)
      ->checkout($tag)
      ->run();
  }

  /**
   * Deploy a branch updates.
   *
   * @param array $options
   *
   * @return void
   * @throws \Robo\Exception\TaskException
   */
  public function deployBranch(array $options = [
    'reset' => TRUE,
    'branch|b' => NULL,
  ]) {
    $this->deploymentBranch = $options['branch'] ?? $this->getConfigVal('deployment.branch') ?? $this->getCurrentBranch();

    if (!$this->isDeploymentEnvironment) {
      throw new \RuntimeException("Cannot perform deployment within configuration.");
    }
    if (!$this->deploymentBranch) {
      throw new \RuntimeException("Cannot perform deployment. No branch defined.");
    }

    $this->taskExecStack()
      ->stopOnFail()
      ->dir($this->appRoot)
      ->exec('git fetch --all --prune')
      ->run();

    $this->taskGitStack()
      ->stopOnFail()
      ->dir($this->appRoot)
      ->checkout($this->deploymentBranch)
      ->pull($this->deploymentRemote, $this->deploymentBranch)
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