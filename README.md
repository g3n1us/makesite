# Makesite

## Installation
```
composer global require g3n1us/makesite
```

This will also install Laravel Valet. Make sure that your `$PATH` includes the following:

```
$HOME/.composer/vendor/bin
```

Next, install Valet and Makesite...

```
makesite install
```

> You'll need to enter your password. After this you should not need to enter your password to create sites

Follow the prompts, and then you will be ready to go

## Usage

### Create a Site

```
makesite [subdomain]
```

> If you don't enter a subdomain, you will be asked interactively what it should be

### Delete a Site

```
makesite -d|--delete <subdomain>
```

This will remove the site directory and associated configurations

