# Code Generator

In this library AMQP frames are represented by PHP classes, providing very handy and easy to use Frame API to the reset of the code. 
Almost everything in `src/Framing` is automatically generated from [AMQP 0.9.1 specification](https://www.rabbitmq.com/resources/specs/amqp0-9-1.extended.xml).
There is no reason to re-generate these classes, unless specification changes or new extensions are added. It means most likely you would never need to run `generate.php` yourself.
