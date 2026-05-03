<p align="center">
    <a href="https://bigpixelrocket.com/deploy-core" target="_blank">
        <picture>
            <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/bigpixelrocket/deploy-core/main/docs/images/logo-mark-dark.svg">
            <source media="(prefers-color-scheme: light)" srcset="https://raw.githubusercontent.com/bigpixelrocket/deploy-core/main/docs/images/logo-mark-light.svg">
            <img src="https://raw.githubusercontent.com/bigpixelrocket/deploy-core/main/docs/images/logo-mark-light.svg" width="auto" alt="DeployCore Logo">
        </picture>
    </a>
</p>

<p align="center">
    <a href="https://packagist.org/packages/bigpixelrocket/deploy-core"><img src="https://img.shields.io/badge/php-%5E8.3-blue.svg" alt="Supports PHP >= 8.3"></a>
    <a href="https://packagist.org/packages/bigpixelrocket/deploy-core"><img src="https://img.shields.io/packagist/v/bigpixelrocket/deploy-core" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/bigpixelrocket/deploy-core"><img src="https://img.shields.io/packagist/l/bigpixelrocket/deploy-core" alt="License"></a>
</p>

<p align="center">
    <a href="https://bigpixelrocket.com/deploy-core/">https://bigpixelrocket.com/deploy-core</a>
</p>

# Meet DeployCore

This is DeployCore, a complete set of CLI tools for provisioning, installing, and deploying servers and sites using PHP. It serves as an open-source alternative to services such as Ploi, RunCloud or Laravel Forge.

Here it is in action:

<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/bigpixelrocket/deploy-core/main/docs/images/deploy-core-dark.webp">
        <source media="(prefers-color-scheme: light)" srcset="https://raw.githubusercontent.com/bigpixelrocket/deploy-core/main/docs/images/deploy-core.webp">
        <img src="https://raw.githubusercontent.com/bigpixelrocket/deploy-core/main/docs/images/deploy-core.webp" width="auto" alt="DeployCore in action">
    </picture>
</p>

<!-- toc -->

- [Crash Course](#crash-course)
- [Benefits](#benefits)
    - [Unlimited Servers & Sites](#unlimited-servers--sites)
    - [No Vendor Lock-In](#no-vendor-lock-in)
    - [End-To-End Management](#end-to-end-management)
    - [Composable Commands](#composable-commands)
    - [AI Automation](#ai-automation)
- [License](#license)
- [Contributing](#contributing)

<!-- /toc -->

<a name="crash-course"></a>

## Crash Course

Here's a quick but complete run-through to start deploying immediately. Run each of these commands in sequence to go from zero to deploy:

```shell
# Install DeployCore
composer require --dev bigpixelrocket/deploy-core
alias deploy="./vendor/bin/deploy"

# Add your server to the inventory
deploy server:add

# Alternatively, set up a cloud instance and add it to
# the inventory automatically with a single command:
#
# $> deploy aws:provision
# $> deploy do:provision

# Install Nginx, PHP, Bun and generate a deploy key
deploy server:install

# Optionally, install your preferred database service:
#
# $> deploy mariadb:install
# $> deploy postgresql:install
# $> deploy redis:install
# $> deploy memcached:install

# Create a site
deploy site:create

# Optionally, create deployment scripts and upload shared files:
#
# $> deploy scaffold:scripts
# $> deploy site:shared:push

# Deploy your application
deploy site:deploy

# Enable HTTPS
deploy site:https
```

<a name="benefits"></a>

## Benefits

<a name="unlimited-servers--sites"></a>

### Unlimited Servers & Sites

There aren't any limits or restrictions on how many servers and sites you can deploy or manage: provision, install, manage, and deploy as many as you want.

<a name="no-vendor-lock-in"></a>

### No Vendor Lock-In

You can manage servers and deploy sites with any hosting or cloud provider. If your server runs Ubuntu LTS and you can SSH into it, you can manage it with DeployCore.

<a name="end-to-end-management"></a>

### End-To-End Management

With DeployCore, you can effortlessly provision cloud instances, install services, and manage deployments and operations directly from the command line.

<a name="composable-commands"></a>

### Composable Commands

Atomic commands allow you to easily spin up new servers and create automation pipelines for running your own custom workflows on demand.

<a name="ai-automation"></a>

### AI Agent Support

Use your favorite AI agents to debug server and site issues, using DeployCore's built-in agent skills scaffolding.

<a name="license"></a>

## License

DeployCore is open-source software distributed under the [MIT License](/LICENSE).

You can use it freely for personal or commercial projects, without any restrictions.

This also means there are no guarantees or warranties. You are on your own.

<a name="contributing"></a>

## Contributing

Thank you for considering contributing to DeployCore!

Please see [CONTRIBUTING](/CONTRIBUTING) for details.
