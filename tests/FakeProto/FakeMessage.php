<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: fake.proto

namespace Softonic\LaravelProtobufEvents\FakeProto;

use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>Softonic.LaravelProtobufEvents.FakeProto.FakeMessage</code>
 */
class FakeMessage extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string content = 1;</code>
     */
    protected $content = '';

    /**
     * Constructor.
     *
     * @param array  $data {
     *                     Optional. Data for populating the Message object.
     * @type  string $content
     *               }
     */
    public function __construct($data = null)
    {
        \GPBMetadata\Fake::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string content = 1;</code>
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Generated from protobuf field <code>string content = 1;</code>
     *
     * @param  string $var
     * @return $this
     */
    public function setContent($var)
    {
        GPBUtil::checkString($var, true);
        $this->content = $var;

        return $this;
    }
}
