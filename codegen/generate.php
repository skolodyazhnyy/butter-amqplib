<?php

$dom = new DOMDocument();
$dom->load('https://www.rabbitmq.com/resources/specs/amqp0-9-1.extended.xml');

$domains = iterator_to_array(parse_domains($dom->getElementsByTagName('amqp')[0]));
$schema = iterator_to_array(parse_classes($dom->getElementsByTagName('amqp')[0], $domains));
$properties = iterator_to_array(parse_basic_properties($dom->getElementsByTagName('amqp')[0], $domains));

export_files(generate_method_classes($schema), __DIR__.'/../src/Framing');
export_files(generate_method_meta_class($schema), __DIR__.'/../src/Framing');
export_files(generate_content_header_class($properties), __DIR__.'/../src/Framing');

function parse_domains(DOMElement $dom) {
    foreach ($dom->getElementsByTagName('domain') as $domain) {
        yield $domain->attributes->getNamedItem('name')->nodeValue =>
            $domain->attributes->getNamedItem('type')->nodeValue;
    }
}

function parse_classes(DOMElement $dom, $domains) {
    foreach ($dom->getElementsByTagName('class') as $class) {
        yield [
            'class-name' => ucfirst(camel_case($class->attributes->getNamedItem('name')->nodeValue)),
            'class-id' => $class->attributes->getNamedItem("index")->nodeValue,
            'methods' => iterator_to_array(parse_methods($class, $domains)),
        ];
    }
}

function parse_methods(DOMElement $dom, $domains) {
    foreach ($dom->getElementsByTagName('method') as $method) {
        yield [
            'method-name' => ucfirst(camel_case($method->attributes->getNamedItem('name')->nodeValue)),
            'method-id' => $method->attributes->getNamedItem("index")->nodeValue,
            'method-label' => $method->attributes->getNamedItem("label") ?
                $method->attributes->getNamedItem("label")->nodeValue : null,
            'fields' => parse_prepare_bit_groups(iterator_to_array(parse_fields($method, $domains))),
        ];
    }
}

function parse_prepare_bit_groups($fields)
{
    reset($fields);
    $firstInGroupIndex = null;

    while ($bit = next($fields)) {
        if ($bit['type'] != 'bit') {
            $firstInGroupIndex = null;
            continue;
        }

        if ($firstInGroupIndex == null || count($fields[$firstInGroupIndex]['bit-group']) >= 7) {
            $firstInGroupIndex = key($fields);
            $fields[$firstInGroupIndex]['bit-group'] = [];
            continue;
        }

        $fields[$firstInGroupIndex]['bit-group'][] = $bit;
        $fields[key($fields)]['skip-encoding'] = true;
    }

    return $fields;
}

function parse_fields(DOMElement $dom, $domains) {
    foreach ($dom->getElementsByTagName('field') as $field) {
        if ($field->attributes->getNamedItem('type')) {
            $type = $field->attributes->getNamedItem('type')->nodeValue;
        } else
        if ($field->attributes->getNamedItem('domain')) {
            $type = $domains[$field->attributes->getNamedItem('domain')->nodeValue];
        } else {
            throw new \Exception('Unknown field type: neither domain nor type is set');
        }

        $getterPrefix = php_type($type) == 'bool' ? 'is' : 'get';

        yield [
            'name' => camel_case($field->attributes->getNamedItem('name')->nodeValue),
            'getter' => $getterPrefix.ucfirst(camel_case($field->attributes->getNamedItem('name')->nodeValue)),
            'label' => $field->attributes->getNamedItem('label') ?
                $field->attributes->getNamedItem('label')->nodeValue : null,
            'type' => $type,
            'php-type' => php_type($type),
            'amqp-type' => amqp_type($type),
        ];
    }
}

function parse_basic_properties(DOMElement $dom, $domains)
{
    $xpath = new DOMXPath($dom->ownerDocument);
    $fields = $xpath->query('//class[@name=\'basic\']/field');

    foreach ($fields as $field) {
        if ($field->attributes->getNamedItem('type')) {
            $type = $field->attributes->getNamedItem('type')->nodeValue;
        } else
            if ($field->attributes->getNamedItem('domain')) {
                $type = $domains[$field->attributes->getNamedItem('domain')->nodeValue];
            } else {
                throw new \Exception('Unknown field type: neither domain nor type is set');
            }

        $getterPrefix = php_type($type) == 'bool' ? 'is' : 'get';

        yield [
            'name' => $field->attributes->getNamedItem('name')->nodeValue,
            'getter' => $getterPrefix.ucfirst(camel_case($field->attributes->getNamedItem('name')->nodeValue)),
            'label' => $field->attributes->getNamedItem('label') ?
                $field->attributes->getNamedItem('label')->nodeValue : null,
            'type' => $type,
            'php-type' => php_type($type),
            'amqp-type' => amqp_type($type),
        ];
    }
}

function generate_method_classes($schema) {
    foreach ($schema as $class) {
        foreach ($class['methods'] as $method) {
            yield 'Method/'.$class['class-name'].$method['method-name'].'.php' => implode('', [
                generate_method_classes_header($class, $method),
                generate_method_classes_properties($class, $method),
                generate_method_classes_constructor($class, $method),
                generate_method_classes_getters($class, $method),
                generate_method_classes_encoder($class, $method),
                generate_method_classes_footer(),
            ]);
        }
    }
}

function generate_method_classes_header($class, $method)
{
    $phpClassName = $class['class-name'].$method['method-name'];
    $label = ucfirst($method['method-label']);
    $phpdoc = $label ? "\n/**\n * {$label}.\n */" : "";

    return <<<HEADER
namespace AMQPLib\Framing\Method;

use AMQPLib\Buffer;
use AMQPLib\Framing\Method;
use AMQPLib\Value;
{$phpdoc}
class {$phpClassName} extends Method
{
HEADER;
}

function generate_method_classes_properties($class, $method)
{
    $code = '';

    foreach ($method['fields'] as $field) {
        $init = $field['php-type'] === 'array' ? ' = []' : '';

        $code .= <<<FIELD

    /**
     * @var {$field['php-type']}
     */
    private \${$field['name']}{$init};

FIELD;
    }

    return $code;
}

function generate_method_classes_constructor($class, $method)
{
    if (!count($method['fields'])) {
        return '';
    }

    $constructArguments = [];
    $constructBody = [];
    $constructPHPDoc = [];

    foreach ($method['fields'] as $field) {
        $constructArguments[] = "\${$field['name']}";
        $constructBody[] = "\$this->{$field['name']} = \${$field['name']};";
        $constructPHPDoc[] = " * @param {$field['php-type']} \${$field['name']}";
    }

    $constructArguments = implode(', ', $constructArguments);
    $constructBody = implode("\n        ", $constructBody);
    $constructPHPDoc = implode("\n    ", $constructPHPDoc);

    return <<<CONSTRUCT

    /**
    {$constructPHPDoc}
     */
    public function __construct({$constructArguments})
    {
        {$constructBody}
    }

CONSTRUCT;
}

function generate_method_classes_getters($class, $method)
{
    $code = '';

    foreach ($method['fields'] as $field) {
        $label = ucfirst($field['label'] ?: $field['name']);

        $code .= <<<GETTER

    /**
     * {$label}.
     *
     * @return {$field['php-type']}
     */
    public function {$field['getter']}()
    {
        return \$this->{$field['name']};
    }

GETTER;
    }

    return $code;
}

function generate_method_classes_encoder($class, $method)
{

    $encoderBody = ["\"".php_string_short($class['class-id']).php_string_short($method['method-id'])."\""];
    $decoderBody = [];

    foreach ($method['fields'] as $field) {
        if (!empty($field['skip-encoding'])) {
            continue;
        }

        if ($field['type'] == 'bit' && !empty($field['bit-group'])) {
            $shift = 1;
            $bitEncoderBody = ["(\$this->{$field['name']} ? 1 : 0)"];
            foreach ($field['bit-group'] as $bit) {
                $bitEncoderBody[] = "((\$this->{$bit['name']} ? 1 : 0) << $shift)";
                $shift++;
            }

            $encoderBody[] = "Value\\OctetValue::encode(".implode(" | ", $bitEncoderBody).")";

            $decoderBody[] = "(bool) (\$flags = Value\\OctetValue::decode(\$data)) & 1";
            $shift = 1;
            foreach ($field['bit-group'] as $bit) {
                $decoderBody[] = "(bool) \$flags & ".(1 << $shift);
                $shift++;
            }

            continue;
        }

        $encoderBody[] = "Value\\".$field['amqp-type']."::encode(\$this->{$field['name']})";
        $decoderBody[] = "Value\\".$field['amqp-type']."::decode(\$data)";
    }

    $encoderBody = implode(".\n            ", $encoderBody);
    $decoderBody = implode(",\n            ", $decoderBody);

    if ($decoderBody) {
        $decoderBody = "\n            $decoderBody\n        ";
    }

    return <<<ENCODER

    /**
     * @return string
     */
    public function encode()
    {
        return {$encoderBody};
    }

    /**
     * @param Buffer \$data
     *
     * @return \$this
     */
    public static function decode(Buffer \$data)
    {
        return new self({$decoderBody});
    }

ENCODER;
}

function generate_method_classes_footer()
{
    return <<<FOOTER
}
FOOTER;
}

function generate_method_meta_class($schema) {
    return ['Method.php' => implode('', [
        generate_method_meta_class_header($schema),
        generate_method_meta_class_decoder($schema),
        generate_method_meta_class_footer($schema),
    ])];
}

function generate_method_meta_class_header($schema)
{
    return <<<HEADER
namespace AMQPLib\Framing;

use AMQPLib\Buffer;
use AMQPLib\Binary;

abstract class Method extends Frame
{
HEADER;
}

function generate_method_meta_class_decoder($schema)
{
    $body = [];
    $body[] = 'switch($class) {';

    foreach ($schema as $class) {
        $body[] = '    case "'.php_string_short($class['class-id']).'":';
        $body[] = '        switch($method) {';
        foreach ($class['methods'] as $method) {
            $className = $class['class-name'].$method['method-name'];
            $body[] = '            case "'.php_string_short($method['method-id']).'":';
            $body[] = "                return Method\\{$className}::decode(\$data);";
        }
        $body[] = '        }';
        $body[] = '        break;';
    }

    $body[] = '}';

    $body = trim(implode("\n", array_map(function($x) { return str_repeat(' ', 8).$x; }, $body)));

    return <<<DECODER
    
    /**
     * @param Buffer \$data
     * 
     * @return \$this
     */
    public static function decode(Buffer \$data)
    {
        \$class = \$data->read(2);
        \$method = \$data->read(2);
    
        {$body}
        
        throw new \InvalidArgumentException(sprintf(
            'Invalid method received %d:%d',
            Binary::unpackbe('s', \$class),
            Binary::unpackbe('s', \$method)
        ));
    }

DECODER;
}

function generate_method_meta_class_footer($schema)
{
    return <<<FOOTER

    /**
     * {@inheritdoc}
     */
    public function getFrameType()
    {
        return "\\x01";
    }
}
FOOTER;
}

function generate_content_header_class($properties)
{
    return ['Header.php' => implode('', [
        generate_content_header_class_header($properties),
        generate_content_header_class_encoder($properties),
        generate_content_header_class_decoder($properties),
        generate_content_header_class_footer($properties),
    ])];
}

function generate_content_header_class_header($properties)
{
    return <<<HEADER
namespace AMQPLib\Framing;

use AMQPLib\Buffer;
use AMQPLib\Binary;
use AMQPLib\Value;

class Header extends Frame
{
    /**
     * @var int
     */
    private \$classId;

    /**
     * @var int
     */
    private \$weight;

    /**
     * @var int
     */
    private \$size;

    /**
     * @var array
     */
    private \$properties = [];

    /**
     * @param int \$classId
     * @param int \$weight
     * @param int \$size
     * @param array \$properties
     */
    public function __construct(\$classId, \$weight, \$size, array \$properties = [])
    {
        \$this->classId = \$classId;
        \$this->weight = \$weight;
        \$this->size = \$size;
        \$this->properties = \$properties;
    }

    /**
     * @return int
     */
    public function getClassId()
    {
        return \$this->classId;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return \$this->weight;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return \$this->size;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return \$this->properties;
    }

HEADER;
}

function generate_content_header_class_encoder($properties)
{
    $propertyWriting = [];
    $shift = 15;

    foreach ($properties as $property) {
        $value = 1 << $shift;
        $propertyWriting[] = "if (array_key_exists('{$property['name']}', \$this->properties)) {";
        $propertyWriting[] = "    \$flags |= {$value};";
        $propertyWriting[] = "    \$properties .= Value\\".$property['amqp-type']."::encode(\$this->properties['{$property['name']}']);";
        $propertyWriting[] = "}\n";
        $shift--;
    }

    $propertyWriting = implode("\n        ", $propertyWriting);

    return <<<ENCODER

    /**
     * @return string
     */
    public function encode()
    {
        \$properties = '';
        \$flags = 0;
        
        {$propertyWriting}
        return Value\ShortValue::encode(\$this->classId).
            Value\ShortValue::encode(\$this->weight).
            Value\LongLongValue::encode(\$this->size).
            Value\ShortValue::encode(\$flags).
            \$properties;
    }

ENCODER;
}

function generate_content_header_class_decoder($properties)
{
    $propertiesReading = [];
    $shift = 15;

    foreach ($properties as $property) {
        $value = 1 << $shift;
        $propertiesReading[] = "if (\$flags & $value) {";
        $propertiesReading[] = "    \$properties['{$property['name']}'] = Value\\".$property['amqp-type']."::decode(\$data);";
        $propertiesReading[] = "}\n";
        $shift--;
    }

    $propertiesReading = implode("\n        ", $propertiesReading);

    return <<<DECODER

    /**
     * @param Buffer \$data
     *
     * @return \$this
     */
    public static function decode(Buffer \$data)
    {
        \$class  = Value\ShortValue::decode(\$data);
        \$weight = Value\ShortValue::decode(\$data);
        \$size   = Value\LongLongValue::decode(\$data);
        \$flags  = Value\ShortValue::decode(\$data);

        \$properties = [];

        {$propertiesReading}
        return new self(\$class, \$weight, \$size, \$properties);
    }

DECODER;
}

function generate_content_header_class_footer($properties)
{
    return <<<HEADER

    /**
     * {@inheritdoc}
     */
    public function getFrameType()
    {
        return "\\x02";
    }
}
HEADER;
}

function print_files($files)
{
    foreach($files as $file => $code) {
        echo $file. ":".PHP_EOL;
        echo $code . PHP_EOL.PHP_EOL;
    }
}

function export_files($files, $path)
{
    $header = <<<HEADER
<?php
/*
 * This file is automatically generated.
 */

HEADER;

    foreach ($files as $file => $code) {
        file_put_contents($path.DIRECTORY_SEPARATOR.$file, $header.$code);
    }
}

function camel_case($name) {
    return preg_replace_callback('#([a-z0-9])-([a-z0-9])#i', function($m) { return $m[1].strtoupper($m[2]); }, $name);
}

function php_type($type)
{
    $map = [
        'octet' => 'int',
        'short' => 'int',
        'long' => 'int',
        'longlong' => 'int',
        'table' => 'array',
        'longstr' => 'string',
        'shortstr' => 'string',
        'bit' => 'bool',
        'timestamp' => 'int',
    ];

    if (!isset($map[$type])) {
        throw new \Exception(sprintf('Type "%s" is not mapped', $type));
    }

    return $map[$type];
}

function amqp_type($type)
{
    $map = [
        'octet' => 'OctetValue',
        'table' => 'TableValue',
        'longstr' => 'LongStringValue',
        'shortstr' => 'ShortStringValue',
        'short' => 'ShortValue',
        'long' => 'LongValue',
        'longlong' => 'LongLongValue',
        'timestamp' => 'LongLongValue',
        'bit' => 'BooleanValue',
    ];

    if (!isset($map[$type])) {
        throw new \Exception(sprintf('Type "%s" is not mapped', $type));
    }

    return $map[$type];
}

function php_string_short($dec)
{
    return "\\x".str_pad(strtoupper(dechex(floor($dec/256))), 2, '0', STR_PAD_LEFT).
        "\\x".str_pad(strtoupper(dechex($dec % 256)), 2, '0', STR_PAD_LEFT);
}
