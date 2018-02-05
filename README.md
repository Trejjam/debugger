Debugger
========

[![Latest stable](https://img.shields.io/packagist/v/trejjam/debugger.svg)](https://packagist.org/packages/trejjam/debugger)

Installation
------------

The best way to install Trejjam/Utils is using  [Composer](http://getcomposer.org/):

```sh
$ composer require trejjam/debugger
```

Configuration
-------------

.neon
```yml
extensions:
	trejjam.debugger: Trejjam\Debugger\DI\DebuggerExtension

trejjam.debugger:
	logger:
		mailService: @Nette\Mail\IMailer
		snoze: '1 day'
		host: NULL #NULL mean auto
		path: '/log/'
	storeAllError: FALSE
	exceptionStorage: NULL #type of Trejjam\Debugger\Exception\IStorage
	blob:
		client: NULL #type of MicrosoftAzure\Storage\Blob\Internal\IBlob
		prefix: NULL #container prefix
		blobSettings: NULL #type of Trejjam\Debugger\Azure\Settings
	
```
