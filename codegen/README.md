# Code Generator

In this library AMQP frames are represented by PHP classes, providing very handy and easy to use Frame API to the rset of the code. 
Almost everything in `src/AMQP091/Framing` is automatically generated from [AMQP 0.9.1 specification](https://www.rabbitmq.com/resources/specs/amqp0-9-1.extended.xml).
There is no reason to re-generate these classes, unless specification changes or new extensions are added. It means most likely you would never need to run `generate.php` yourself.

But in case you want to change automatically generated code:

- Do not change generated code itself as it will be overriden by code generator, instead change template in `generate.php`
- Once new code is generated run `php-cs-fixer` (installed as dev dependency) to fix coding standard issues
