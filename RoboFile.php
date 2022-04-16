<?php

use DagLab\RoboDeploy\EnvironmentFactory;
use DagLab\RoboDeploy\Model\EnvironmentInterface;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
  use \Kerasai\Robo\Config\ConfigHelperTrait;

  protected $appRoot;

  /**
   * @var array
   */
  protected $environmentsConfig = [];

  /**
   * @var \DagLab\RoboDeploy\Model\EnvironmentInterface[]
   */
  protected $environments = [];

  /**
   * Filename for the app's version file.
   *
   * @var mixed|string
   */
  protected $versionFilename = '.robo-deploy-version';

  /**
   * Absolute path to version file.
   *
   * @var string
   */
  protected $versionFile;

  /**
   * Options for versioning strategies.
   *
   * @var string[]
   */
  protected $versionStrategies = ['datetime', 'incremental'];

  /**
   * Current version strategy.
   *
   * @var mixed|string
   */
  protected $versionStrategy = 'datetime';

  /**
   * RoboFile constructor.
   */
  public function __construct() {
    $this->environmentsConfig = $this->requireConfigVal('environments');

    if ($this->getConfigVal('version.filename')) {
      $this->versionFilename = $this->getConfigVal('version.filename');
    }
    if ($this->getConfigVal('version.strategy') && in_array($this->getConfigVal('version.strategy'), $this->versionStrategies)) {
      $this->versionStrategy = $this->getConfigVal('version.strategy');
    }
  }

  /**
   * @return \DagLab\RoboDeploy\Model\EnvironmentInterface[]
   * @throws \Robo\Exception\TaskException
   */
  protected function getEnvironments() {
    if ($this->environments) {
      return $this->environments;
    }

    if ($this->environmentsConfig) {
      foreach ($this->environmentsConfig as $name => $details) {
        $environment = EnvironmentFactory::createFromConfig($name, $details, $this->versionFilename);

        // Default branches are the current branch name.
        if ($environment->isReleaseEnvironment() && !$environment->getReleaseDetails()->getBranch()) {
          $environment->getReleaseDetails()->setBranch($this->getCurrentBranch($environment));
        }
        if ($environment->isDeploymentEnvironment() && !$environment->getDeploymentDetails()->getBranch()) {
          $environment->getDeploymentDetails()->setBranch($this->getCurrentBranch($environment));
        }

        $this->environments[$environment->getName()] = $environment;
      }
    }

    return $this->environments;
  }

  /**
   * @param string $name
   *
   * @return bool
   * @throws \Robo\Exception\TaskException
   */
  protected function hasEnvironment(string $name) {
    return isset($this->getEnvironments()[$name]);
  }

  /**
   * @param string $name
   *
   * @return \DagLab\RoboDeploy\Model\EnvironmentInterface
   * @throws \Robo\Exception\TaskException
   */
  protected function getEnvironment(string $name) {
    if ($this->hasEnvironment($name)) {
      return $this->getEnvironments()[$name];
    }

    throw new \RuntimeException("Environment not found: {$name}");
  }

  /**
   * Validate configuration.
   *
   * @return void
   * @throws \Robo\Exception\TaskException
   */
  public function configValidate() {
    dump([
      'version strategy' => $this->versionStrategy,
      'version file' => $this->versionFile,
      'environments' => $this->getEnvironments(),
    ]);
  }

  /**
   * Increment version and push commit.
   *
   * @param string $environment_name
   * @param array $options
   *
   * @return \Robo\Result|null
   * @throws \Robo\Exception\TaskException
   */
  public function releaseCommit(string $environment_name, array $options = [
    'message|m' => 'Creating release commit.',
  ]) {
    $environment = $this->getEnvironment($environment_name);
    if (!$environment->isReleaseEnvironment()) {
      throw new \RuntimeException("Environment {$environment->getName()} is not configured for release.");
    }

    $this->incrementVersion($environment);
    $git = $this->taskGitStack()
      ->stopOnFail()
      // Commit and push the release branch.
      ->dir($environment->getRepoRoot())
      ->add('-A')
      ->checkout($environment->getReleaseDetails()->getBranch())
      ->commit($options['message'])
      ->push(
        $environment->getReleaseDetails()->getRemote(),
        $environment->getReleaseDetails()->getBranch()
      );

    if ($environment->getReleaseDetails()->shouldSyncBranches()) {
      foreach ($environment->getReleaseDetails()->getSyncBranches() as $branch) {
        $git
          // Checkout sync branch, merge with release branch, and push.
          ->checkout($branch)
          ->merge($environment->getReleaseDetails()->getBranch())
          ->push(
            $environment->getReleaseDetails()->getRemote(),
            $branch
          );
      }

      // Return to the release branch.
      $git->checkout($environment->getReleaseDetails()->getBranch());
    }

    return $git->run();
  }

  /**
   * Tag the current version and push.
   *
   * @param string $environment_name
   *
   * @return \Robo\Result|null
   * @throws \Robo\Exception\TaskException
   */
  public function releaseTag(string $environment_name) {
    $environment = $this->getEnvironment($environment_name);
    if (!$environment->isReleaseEnvironment()) {
      throw new \RuntimeException("Environment {$environment->getName()} is not configured for release.");
    }

    $version = $this->getAppVersion($environment);
    return $this->taskGitStack()
      ->stopOnFail()
      ->dir($environment->getRepoRoot())
      ->checkout($environment->getReleaseDetails()->getBranch())
      ->tag($version)
      ->push($environment->getReleaseDetails()->getRemote(), $version)
      ->run();
  }

  /**
   * Deploy a tag of the environment.
   *
   * @param string $environment_name
   * @param array $options
   *
   * @return \Robo\Result|null
   * @throws \Robo\Exception\TaskException
   */
  public function deployTag(string $environment_name, array $options = [
    'reset' => TRUE,
    'tag|t' => NULL,
  ]) {
    $environment = $this->getEnvironment($environment_name);
    if (!$environment->isDeploymentEnvironment()) {
      throw new \RuntimeException("Environment {$environment->getName()} is not configured for deployment.");
    }

    $tag = $options['tag'];

    // Fetch changes and checkout the latest version file.
    if (!$tag) {
      $this->taskExecStack()
        ->stopOnFail()
        ->dir($environment->getRepoRoot())
        ->exec('git fetch --all --prune')
        ->exec("git checkout {$environment->getDeploymentDetails()->getRemote()}/{$environment->getDeploymentDetails()->getBranch()} -- {$this->versionFilename}")
        ->run();

      $tag = $this->getAppVersion($environment);
    }

    if ($options['reset']) {
      $this->taskExecStack()
        ->stopOnFail()
        ->dir($environment->getRepoRoot())
        ->exec("git reset --hard {$environment->getDeploymentDetails()->getRemote()}/{$environment->getDeploymentDetails()->getBranch()}")
        ->run();
    }

    return $this->taskGitStack()
      ->stopOnFail()
      ->dir($environment->getRepoRoot())
      ->checkout($tag)
      ->run();
  }

  /**
   * Deploy a branch updates.
   *
   * @param string $environment_name
   * @param array $options
   *
   * @return \Robo\Result|null
   * @throws \Robo\Exception\TaskException
   */
  public function deployBranch(string $environment_name, array $options = [
    'reset' => TRUE,
    'branch|b' => NULL,
  ]) {
    $environment = $this->getEnvironment($environment_name);
    if (!$environment->isDeploymentEnvironment()) {
      throw new \RuntimeException("Environment {$environment->getName()} is not configured for deployment.");
    }

    $this->taskExecStack()
      ->stopOnFail()
      ->dir($environment->getRepoRoot())
      ->exec('git fetch --all --prune')
      ->run();

    return $this->taskGitStack()
      ->stopOnFail()
      ->dir($environment->getRepoRoot())
      ->checkout($environment->getDeploymentDetails()->getBranch())
      ->pull($environment->getDeploymentDetails()->getRemote(), $environment->getDeploymentDetails()->getBranch())
      ->run();
  }

  /**
   * Get the current tracked app version.
   *
   * @param \DagLab\RoboDeploy\Model\EnvironmentInterface $environment
   *
   * @return string
   */
  protected function getAppVersion(EnvironmentInterface $environment) {
    return $this->taskSemVer($environment->getVersionFile()->getPath())->__toString();
  }

  /**
   * Get the name of the branch the app is on.
   *
   * @param \DagLab\RoboDeploy\Model\EnvironmentInterface $environment
   *
   * @return string|null
   */
  protected function getCurrentBranch(EnvironmentInterface $environment) {
    $result = $this->taskExec('git')
      ->dir($environment->getRepoRoot())
      ->args('symbolic-ref', '--short', 'HEAD')
      ->printOutput(false)
      ->run();

    return $result->getOutputData() ?: NULL;
  }

  /**
   * Increment tracked app version.
   *
   * @param \DagLab\RoboDeploy\Model\EnvironmentInterface $environment
   *
   * @return \Robo\Result
   * @throws \Robo\Exception\TaskException
   */
  protected function incrementVersion(EnvironmentInterface $environment) {
    $semver = $this->taskSemVer($environment->getVersionFile()->getPath());
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

    return $semver->run();
  }

}