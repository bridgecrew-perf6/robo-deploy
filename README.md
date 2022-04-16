# Robo Deploy

Utility for handling deployments for a self-hosted website.

## Requirements

* Server access
* Composer

## Commands

| Command                                  | Description                                                                        |
|------------------------------------------|------------------------------------------------------------------------------------|
| `robo config:validate`                   | Simple robo.yml validation.                                                        |
| `robo release:commit <environment name>` | Add, commit, and push all existing file changes.                                   |
| `robo release:tag <environment name>`    | Tag the current git status and push the tag.                                       |
| `robo deploy:branch <environment name>`  | Checkout and pull a branch.                                                        |
| `robo deploy:tag <environment name>`     | Fetch changes and checkout the tag that correlates with the version file contents. |


## Examples

| File                                                                                                      | Description                                                                                                           |
|-----------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------|
| [`robo.yml`](examples/robo.yml)                                                                           | Example configuration file.                                                                                           |
| [`github-workflow.release-test--deploy-live.yml`](examples/github-workflow.release-test--deploy-live.yml) | Example GitHub action that performs `release:commit` & `releas:tag` in one environment, then `deploy:tag` in another. |

## Setup

* Get this software on the server. Somewhere outside of the docroot.
* Copy appropriate `examples/robo.example-*.yml` file to the root of this software on the server.
