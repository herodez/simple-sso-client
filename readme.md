# Simple SSO-client bundle 

[![Latest Stable Version](http://img.shields.io/badge/Latest%20Stable-1.1.1-green.svg)](http://optgit.optimeconsulting.net:8090/component/optime_sso_client)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg?style=flat-square)](https://php.net/)

This repository contains a SSO-client bundle that allows you authenticate user in your symfony application via a SSO-server.

## Installation

The SSO-client bundle can be installed with [Composer](https://getcomposer.org/). following the next steps:

### Add Optime private repository 

First at all add the Optime private composer repository in your project
```json
{
  "repositories": [
    {
	  "type": "composer",
	  "url": "http://satis.developerplace.net/"
    }
  ]
}
```
Because the Optime private repository not allow HTTP secure connections for now, you must be allow 
insecure HTTP connections in your composer configuration in order to install packages from this repository, 
add the following line in the configuration section of your **composer.json** file to allow insecure HTTP
connections.

```json
{
    "config": {
      "secure-http": false
    }
}
```
> **Note:** check the official composer documentation for more information about private repositories
configuration

then install the package via composer
```sh
composer require optime/simple-sso-client
```
## Usage

After install register the bundle in the application bundles section.

```php
return [
    ....
    Optime\SimpleSsoClientBundle\SimpleSsoClientBundle::class => ['all' => true]
];
```
and register the SSO authenticator in your **security.yaml** file as entry point authenticator
```yaml
guard:
  authenticators:
    - another_authenticator
    - simple_sso_client.security.simple_sso_authenticator
  entry_point: simple_sso_client.security.simple_sso_authenticator
```

### Configuration

To configure the bundle create a **simple_sso_client.yaml** file with the following parameters:
```yaml
simple_sso_client:
  default_server: Application 
  user_factory: app-user-factory
  server_id_on_path: {true|false}
  servers:
    Application:
      server_id: Application-id
      username: Application-username
      password: Application-password
      url: Application-sso-login-url
```
#### parameters

- **default_server**: Configure a default SSO serve to use as primary server authenticator
is necessary only if you configure the **simple SSO client bundle** as primary login system

- **user_factory**: Configure the primary method to validate and create external user
for your application.

- **server_id_on_path**: Configure if the _sso_server_id will be on the path

- **servers**: Configure an array of SSO servers that your application allow for authenticate user.

- **server_id**: Configure the SSO server identifier

- **username**: Configure the SSO server username 

- **password**: Configure the SSO server password

- **url**: Configure the SSO server URL

#### User provider 

In order to use the Simple SSO-client authenticator you must be has at least one user provider 
in the **security.yaml** file's providers section, this provider must be an user provider implementation, not working with the `in_memory` user provider, if you don't have an user provider you can use
the Simple SSO-client user provider as following:

```yaml
providers:
  loyalty:
    simple_sso:
      id: simple_sso_client.security.simple_sso_user_provider
```
#### Configure as primary login system

To configure the **Simple SSO client bundle** as primary login system you must be configure in your 
**security.yaml** file the main application guard, to do that you must be register
a SSO server previously and mark that as default SSO server, the following configure the sso authenticator as 
primary login system.
```yaml
main:
    pattern: ^/    
    guard:
        authenticators:
            - simple_sso_client.security.simple_sso_authenticator
```
#### Login with multiple SSO servers

To authenticate a user with a specific SSO server, make a request to any URI with 
the following query parameter: 

**_sso_server_id**: Identify the SSO server to use to authenticate the user.

>**Example**: dominio_app/?_sso_server_id={server_id}

or if you has configure the server_id_on_path option as true then you can use
the following **URL** schema:

>**Example**: dominio_app/{server_id}

## Security Vulnerabilities

If you have found a security issue, please contact the maintainers directly at [mgonzalez@optimeconsulting.com](mailto:mgonzalez@optimeconsulting.com).
