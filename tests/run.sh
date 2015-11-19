#!/bin/bash
# pear install --alldeps phpunit
# php composer.phar require "phpunit/phpunit=4.7.*"

../vendor/bin/phpunit --stop-on-failure --bootstrap ../src/autoload.php APIAccessTest